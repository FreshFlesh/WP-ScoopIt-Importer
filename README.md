# WP Scoop.It Importer

Import automatically all curated posts from a specific Scoop.It topic as WordPress CPT.

Works with Wordpress Cron.

Scoop image is set as post featured image if supported by the Cutom Post Type.



# Configure

Go to Settings -> Scoop.It Importer to configure the plugin.


# Filters & Actions


### scoopit_wp_insert_post_data (filter)
Filters $post_data content before it is inserted via the wp_insert_post function.
Useful to add or customize default fields to the post content.

It also receive as a second argument the scoop data received from Scoop.It. 

Should return a $post_data array that suits the wp_insert_post function : http://codex.wordpress.org/Function_Reference/wp_insert_post



###  scoopit_after_wp_insert_post (action)

Lets you do further data processing after the post has been created.
Useful to create additional custom fields.

Receive 2 parameters :

$post_id : the ID of the created post

$curated_post : the scoop data received from Scoop.It. 


Exemple : 

    add_action( 'scoopit_after_wp_insert_post', 'add_html_fragment', 10, 2 );
    
    // feed custom fields with data from scoop.it
    function add_html_fragment($post_id, $curated_post) {
        add_post_meta( $post_id, 'html_fragment', $curated_post->url );
    }