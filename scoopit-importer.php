<?php
/*
Plugin Name: Scoop.It Importer
Plugin URI: http://june22.eu
Description: Import automatically all curated posts from a specific Scoop.It topic as worpdress posts
Version: 1.2.1
Author: Thomas Charbit
Author URI: https://twitter.com/thomascharbit
Author Email: thomas.charbit@gmail.eu
*/
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ScoopitImporter {

	const SLUG = 'scoopit-importer';
	
    private $defaults = array (
        'scoopit_consumer_key' => null,
        'scoopit_consumer_secret' => null,
        'scoopit_account' => null,
        'scoopit_topic' => null,
        'recurrence' => 'hourly',
        'post_type' => 'post',
        'post_author' => 1,
        'post_status' => 'draft'
    );

    private $post_statuses = array (
    	'draft',
    	'publish',
    	'pending',
    	'private'
    );
    
    private $settings;

	 
	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	
	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {
		// Load plugin text domain
		add_action( 'init', array($this, 'si_textdomain' ) );		
		// Register hooks that are fired when the plugin is activated, deactivated.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		// Hook for cron job
		add_action('scoopit_scheduled_hook',  array(&$this, 'scoopit_import_topic'));
		// Add more time intervals for cron job
		add_filter( 'cron_schedules', array(&$this, 'more_schedules') );
		// Add conf page to admin menu
		add_action('admin_menu', array(&$this, 'admin_menu'));
	} // end constructor
	
	/**
	 * Fired when the plugin is activated.
	 */
	public function activate( $network_wide ) {
	    $this->get_settings();
		wp_schedule_event( time(), $this->settings['recurrence'], 'scoopit_scheduled_hook');
	} // end activate
	
	/**
	 * Fired when the plugin is deactivated.
	 */
	public function deactivate( $network_wide ) {
		wp_clear_scheduled_hook('scoopit_scheduled_hook');
	} // end deactivate

	/**
	 * Loads the plugin text domain for translation
	 */
	public function si_textdomain() {
		$domain = 'scoopit-importer-locale';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	} // end plugin_textdomain


	
	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/

	 
	public function admin_menu() {
        add_options_page(
            'Scoop.It Importer',
            'Scoop.It Importer',
            'manage_options',
            self::SLUG ,
            array(&$this, 'admin_page')
        );
    }

    public function admin_page() {
		// Make sure the user has the required permissions to view the settings.
		if (!current_user_can('manage_options')) {
		    wp_die('Sorry, you don\'t have the permissions to access this page.');
		}
		
		$this->get_settings();
		
		// Form submit handler
		if ( isset( $_POST['scoopit-submit'] ) && check_admin_referer( 'scoopit-form' ) ) {
			$this->settings = array_merge( $this->settings, array(
				'scoopit_consumer_key' => $_POST['si_app_consumer_key'],
				'scoopit_consumer_secret' => $_POST['si_app_consumer_secret'],
				'scoopit_account' => $_POST['si_user_account'],
				'post_type' => $_POST['si_post_type'],
				'post_author' => $_POST['si_post_author'],
				'post_status' => $_POST['si_post_status']
			));

			if ( ( isset($_POST['si_topic'] ) ) && ( !empty($_POST['si_topic'] ) ) )
				$this->settings['scoopit_topic'] = $_POST['si_topic'];
			else $this->settings['scoopit_topic'] = null;
			
			if ($this->settings['recurrence'] != $_POST['si_recurrence']) {
		    	$this->settings['recurrence'] = $_POST['si_recurrence'];
		    	wp_clear_scheduled_hook( 'scoopit_scheduled_hook' );
		    	wp_schedule_event( time(), $this->settings['recurrence'], 'scoopit_scheduled_hook' );
			}
		}
		
		
		include_once( plugin_dir_path(__FILE__) . 'Scoopit-PHP/ScoopIt.php' );
		$scoop = new ScoopIt( new WpTokenStore(), $this->settings['scoopit_consumer_key'], $this->settings['scoopit_consumer_secret'] );
		
		// test app credientials
		try {
			$scoop->test();
			
			if ( isset( $_GET['scoopit-logout'] ) && $scoop->isLoggedIn() ) $scoop->logout();
		    
		    if ( isset( $_GET['oauth_token'] ) && ( isset( $_GET['oauth_verifier'] ) ) ) $scoop->login();
		
		    // test username
		    try {
		    	$userAccountData = $scoop->resolve( 'User', $this->settings['scoopit_account'] );
		        $profile = $scoop->profile($userAccountData->id);
		        $topics = $profile->user->curatedTopics;
		    }
		    catch ( ScoopAuthenticationException $resolveUserError ) {
			    $this->settings['scoopit_topic'] = null;
		    }
		}
		catch ( ScoopAuthenticationException $appCredentialsError ) {
			$this->settings['scoopit_topic'] = null;
		}
		
		$this->save_settings();
		
		include( plugin_dir_path(__FILE__) . 'admin.php' );

    }

    
	public function more_schedules( $schedules ) {
		$more_schedules	= array(
			'fiveminutes' => array(
				'interval' => 300,
				'display' => __('Every 5 minutes')
			),
			'fifteenminutes' => array(
				'interval' => 900,
				'display' => __('Every 15 minutes')
			),
			'twicehourly' => array(
				'interval' => 1800,
				'display' => __('Twice Hourly')
			)
		);
		return array_merge( $schedules,$more_schedules );
	}
	
	public function scoopit_import_topic() {
		global $wpdb;
        $this->get_settings();
		// if no topic is configured, do nothing
		if ( $this->settings['scoopit_topic'] === NULL ) return false;
		
		// load scoopit
		include_once( plugin_dir_path(__FILE__) . 'Scoopit-PHP/ScoopIt.php' );
		$scoop = new ScoopIt( new WpTokenStore(), $this->settings['scoopit_consumer_key'], $this->settings['scoopit_consumer_secret'] );
		// get when we did last update
		$lastupdate_timestamp = get_option( 'scoopit.last_update' );
		if ( ( !$lastupdate_timestamp ) || ( $lastupdate_timestamp== '' ) ) $lastupdate_timestamp = 0;
		// get existing scoopit posts IDs in worpdress
		$query = $wpdb->prepare("
			SELECT	meta_value
			FROM	$wpdb->posts p
			JOIN	$wpdb->postmeta meta
			ON		p.ID = meta.post_ID
			WHERE	p.post_type = %s
			AND		meta.meta_key = %s
			",
			$this->settings['post_type'],
			'_scoopit_id'
		);

		$existingPosts = $wpdb->get_col( $query );
		$createPostError = false;
		$current_time = round( microtime( true ) * 1000 );

		try {
			// get new posts from scoopit
			$topic = $scoop->topic( $this->settings['scoopit_topic'], 999, 0, 0, $lastupdate_timestamp );
			$curatedPosts = array_reverse($topic->curatedPosts );
			$createPostError = false;			
			foreach ( $curatedPosts as $curatedPost ) {

				// check if already exists
				if ( !in_array( $curatedPost->id, $existingPosts) ) {
					
					$attachment_id = $this->get_image($curatedPost->largeImageUrl, 0, $curatedPost->title);
					
					$post_data = array(
						'post_type'		=> $this->settings['post_type'],
						'post_title'	=> $curatedPost->title,
						'post_content'	=> $curatedPost->htmlContent,
						'post_author'	=> $this->settings['post_author'],
						'post_status'	=> $this->settings['post_status'],
						// convert GMT to local time
						'post_date'	=> date( 'Y-m-d H:i:s', $curatedPost->curationDate / 1000 + ( get_option( 'gmt_offset' ) * 3600 ) )
					);
					
					$post_data = apply_filters( 'scoopit_wp_insert_post_data', $post_data, $curatedPost );
					// insert post
					$post_id = wp_insert_post( $post_data );

					// store original scoopit id as custom field
					if ( $post_id > 0 ) {
						add_post_meta( $post_id,'_scoopit_id', $curatedPost->id );
						add_post_meta( $post_id,'_thumbnail_id', $attachment_id );
						if ( ! is_wp_error($attachment_id) ) wp_update_post( array('ID' => $attachment_id, 'post_parent' => $post_id));
						do_action( 'scoopit_after_wp_insert_post',$post_id, $curatedPost );
					}
					else $createPostError = true;
				}
			}

			// import successful, lets update our update timestamp
			if (!$createPostError)
				update_option('scoopit.last_update',$current_time);

		}
		catch ( ScoopAuthenticationException $resolveUserError ) {
			var_dump( $resolveUserError );
		}
	}

	private function prepare_post( $curatedPost ) {

	}
	
    private function get_settings() {
        $this->settings = array(
	        'scoopit_consumer_key'		=> get_option( 'scoopit_consumerKey' ),
	        'scoopit_consumer_secret'	=> get_option( 'scoopit_consumerSecret' ),
	        'scoopit_account'			=> get_option( 'scoopit_account' ),
	        'scoopit_topic'				=> get_option( 'scoopit_topic' ),
	        'recurrence'				=> get_option( 'scoopit_recurrence' ),
	        'post_type'					=> get_option( 'scoopit_post_type' ),
	        'post_author'				=> get_option( 'scoopit_post_author' ),
        	'post_status'				=> get_option( 'scoopit_post_status' ),
        );
        
        array_walk( $this->settings, array( $this, 'merge_settings' ) );
    }

    private function save_settings() {
		update_option( 'scoopit_consumerKey', $this->settings['scoopit_consumer_key'] );
		update_option( 'scoopit_consumerSecret', $this->settings['scoopit_consumer_secret'] );
		update_option( 'scoopit_account', $this->settings['scoopit_account'] );
		update_option( 'scoopit_topic', $this->settings['scoopit_topic'] );
		update_option( 'scoopit_recurrence', $this->settings['recurrence'] );
		update_option( 'scoopit_post_type', $this->settings['post_type'] );
		update_option( 'scoopit_post_author', $this->settings['post_author'] );
		update_option( 'scoopit_post_status', $this->settings['post_status'] );
    }  
    
    private function clear_settings() {
		delete_option( 'scoopit_consumerKey' );
		delete_option( 'scoopit_consumerSecret' );
		delete_option( 'scoopit_account' );
		delete_option( 'scoopit_topic' );
		delete_option( 'scoopit_recurrence' );
		delete_option( 'scoopit_post_type' );
		delete_option( 'scoopit_post_author' );
		delete_option( 'scoopit_post_status' );
    }

	private function merge_settings( &$value, $key ) {
		if ( ( !$value) || $value == '' ) {
			$value = $this->defaults[$key];
		}
	}
	
	private function get_image($file, $post_id, $desc = null){
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$id = null;
		if ( ! empty( $file ) ) {
			// Download file to temp location
			$tmp = download_url( $file );

			$file_ext_and_type = $this->file_ext_and_type( $tmp );
			$file_array['name'] = $desc.'.' . $file_ext_and_type['ext'];
			$file_array['tmp_name'] = $tmp;
			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$id = media_handle_sideload( $file_array, $post_id, $desc );

			// If error storing permanently, unlink
			if ( is_wp_error($id) ) {
				@unlink($file_array['tmp_name']);
			}
			return $id;
		}
		else return false;
	}
	
	private function file_ext_and_type($full_path_to_image='')
	{
	    $extension = 'null';
	    if($image_type = exif_imagetype($full_path_to_image))
	    {
	        $extension = image_type_to_extension($image_type, false);
	    }
	    $known_replacements = array(
	        'jpeg' => 'jpg',
	        'tiff' => 'tif',
	    );
	    $extension = str_replace(array_keys($known_replacements), array_values($known_replacements), $extension);
	    
	    return array('ext' => $extension, 'type' => $image_type);
	}
}

$ScoopitImporter = new ScoopitImporter();
