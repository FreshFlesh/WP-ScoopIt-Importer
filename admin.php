<div class="wrap">
    <div id="icon-options-general" class="icon32"></div>
    <h2><?php _e('Scoop.It Importer'); ?></h2>

    <?php if (isset($_POST['scoopit-submit'])) : ?>
        <div class="updated"><p><?php _e('Settings saved!'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_POST['scoopit-force'])) : ?>
        <div class="updated"><p><?php _e('Import executed.'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo get_admin_url().'options-general.php?page='.self::SLUG; ?>">

      <?php wp_nonce_field('scoopit-form'); ?>


        <h3 class="title"><?php _e('Scoop.It Application Credentials'); ?></h3>
        <p>You need to create an application <a href="https://www.scoop.it/apps" target="_blank">here</a> to access the Scoop.it API. Report below the Consumer Key and Consumer Secret of your application :</p>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="si_app_consumer_key"><?php _e('Application Consumer Key:'); ?></label>
                </th>
                <td>
                    <input class="regular-text ltr"
                           type="text"
                           name="si_app_consumer_key"
                           id="si_app_consumer_key"
                           value="<?php echo $this->settings['scoopit_consumer_key']; ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="si_app_consumer_secret"><?php _e('Application Consumer Secret:'); ?></label>
                </th>
                <td>
                    <input class="regular-text ltr"
                           type="text"
                           name="si_app_consumer_secret"
                           id="si_app_consumer_secret"
                           value="<?php echo $this->settings['scoopit_consumer_secret']; ?>" />
                           <?php if (isset($appCredentialsError) && ($this->settings['scoopit_consumer_secret'] != '' && $this->settings['scoopit_consumer_key'] != '') ) : ?>
                           <p style="color:#c00"><?php _e('Your Consumer Key or Consumer Secret seems incorrect.'); ?></p>
                           <?php elseif ($this->settings['scoopit_consumer_secret'] != '' && $this->settings['scoopit_consumer_key'] != '') : ?>
                           <p style="color:green"><?php _e('Your Consumer Key and Consumer Secret are correct.'); ?></p>
                           <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p>&nbsp;</p>
        
        <h3 class="title"><?php _e('Scoop.it Account & Topic'); ?></h3>        
        
        <table class="form-table">       
            <tr valign="top">
                <th scope="row">
                    <label for="si_user_account"><?php _e('Scoop.it Account:'); ?></label>
                </th>
                <td>
                    <input class="regular-text ltr"
                           type="text"
                           name="si_user_account"
                           id="si_user_account"
                           value="<?php echo $this->settings['scoopit_account']; ?>" />
                           <p class="description"><?php _e('The username of the account you want to retrieve posts from.'); ?></p>
                           <?php if (isset($resolveUserError) && $this->settings['scoopit_account'] != '') : ?>
                           <p style="color:#c00"><?php _e('This username doesn\'t exist.'); ?></p>
                           <?php elseif (!isset($appCredentialsError) && $this->settings['scoopit_account'] != '') : ?>
                           <p style="color:green"><?php _e('This username exists.'); ?></p>
                           <?php endif;?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="si_topic"><?php _e('Topic to import:'); ?></label>
                </th>
                <td>
                  <?php if (isset($topics)) : ?>
                      <?php if (count($topics) > 0) : ?>
                        <select name="si_topic" id="si_topic">
                          <option value=""><?php _e('Please select a topic'); ?></option>
                        <?php foreach ($topics as $topic) : ?>
                          <option value="<?php echo $topic->id ?>" <?php if ($topic->id == $this->settings['scoopit_topic']) echo ' selected="selected"'; ?>><?php echo $topic->name ?></option>
                        <?php endforeach; ?>
                        </select>
                      <?php else : ?>
                        <p class="description"><?php _e('No topic found on this account!'); ?></p>
                      <?php endif; ?>
                  <?php else : ?>
                    <p class="description"><?php _e('Please save settings once with correct app credentials and scoop.it account to select a topic.'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h3 class="title"><?php _e('Refresh Rate'); ?></h3>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="si_recurrence"><?php _e('Refresh every:'); ?></label>
                </th>
                <td>
               <?php $rates = wp_get_schedules(); ?>
               <select name="si_recurrence" id="si_recurrence">
                 <?php foreach ($rates as $name => $rate) : ?>
                 <option value="<?php echo $name ?>" <?php if ($name == $this->settings['recurrence']) echo ' selected="selected"'; ?>><?php echo $rate['display'] ?></option>
                 <?php endforeach; ?>
               </select>
                           
                    <p class="description"><?php _e('You can add custom rates using the <code>cron_schedules</code> filter.'); ?></p>
                </td>
            </tr>
        </table>   

        <h3 class="title"><?php _e('Post Type and Author'); ?></h3>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="si_post_type"><?php _e('Post type:'); ?></label>
                </th>
                <td>
               <?php $post_types = get_post_types(array('public' => true)); ?>
               <select name="si_post_type" id="si_post_type">
                 <?php foreach ($post_types as $name => $post_type) : ?>
                 <?php if (post_type_supports( $post_type, 'custom-fields')) : ?>
                 <option value="<?php echo $name ?>" <?php if ($name == $this->settings['post_type']) echo ' selected="selected"'; ?>><?php echo $post_type ?></option>
                 <?php endif; endforeach; ?>
               </select>
                           
                <p class="description"><?php _e('Choose the type of post you want to create when importing Scoop.it posts. It has to have custom fields capability.'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="si_post_status"><?php _e('Post status:'); ?></label>
                </th>
                <td>
               <select name="si_post_status" id="si_post_status">
                 <?php foreach ($this->post_statuses as $status) : ?>
                 <option value="<?php echo $status ?>" <?php if ($status == $this->settings['post_status']) echo ' selected="selected"'; ?>><?php _e($status); ?></option>
                 <?php endforeach; ?>
               </select>
                           
                <p class="description"><?php _e('Choose the created post status.'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="si_post_author"><?php _e('Post Author:'); ?></label>
                </th>
                <td>
               <?php $authors = get_users(array('who' => 'authors')); ?>
               <select name="si_post_author" id="si_post_author">
                 <?php foreach ($authors as $author) : ?>
                 <option value="<?php echo $author->ID ?>" <?php if ($author->ID == $this->settings['post_author']) echo ' selected="selected"'; ?>><?php echo $author->display_name ?></option>
                 <?php endforeach; ?>
               </select>
                <p class="description"><?php _e('Choose the created post author.'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input class="button button-primary"
                   type="submit"
                   name="scoopit-submit"
                   id="scoopit-submit"
                   value="Save Changes" />
        </p>

    </form>

    <h3 class="title"><?php _e('Scoop.It OAuth'); ?></h3>

    <?php if (!isset($appCredentialsError)) : ?>
    
    <p><?php _e('This is not mandatory, but can be useful to access private topics.'); ?></p>
    
        <?php if ($scoop->isLoggedIn()): ?>
        
        <p><?php _e('You are currently logged in as'); ?> <strong><?php echo $scoop->profile(null)->user->name; ?></strong>. <a class="button" href="<?php echo 'options-general.php?page='.self::SLUG.'&scoopit-logout=1' ?>"><?php _e('logout'); ?></a></p>
        
        <?php else : ?>
        
        <p><?php _e('You are not logged in.'); ?> <a class="button" href="<?php echo $scoop->getLoginUrl(get_admin_url().'options-general.php?page='.self::SLUG); ?>"><?php _e('Log in'); ?></a></p>
        
        <?php endif; ?>
    
    <?php else : ?>

    <p><?php _e('Please enter your Scoop.it application credentials to use OAuth.'); ?></p>
    
    <?php endif; ?>
    
    <p>&nbsp;</p>
    
    <h3 class="title"><?php _e('Statistics'); ?></h3>
    
    <p>Last import : <strong><?php echo date('Y-m-d H:i:s', get_option('scoopitimporter.last_update'));?></strong></p>

    <form method="post" action="<?php echo get_admin_url().'options-general.php?page='.self::SLUG; ?>">
      <?php wp_nonce_field('scoopit-form-force'); ?>
            <input class="button button-primary"
                   type="submit"
                   name="scoopit-force"
                   id="scoopit-force"
                   value="Force import now" />
    </form>
</div>
