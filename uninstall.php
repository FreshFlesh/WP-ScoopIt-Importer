<?php

if(!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();
wp_clear_scheduled_hook('scoopit_scheduled_hook');
delete_option( 'scoopit_consumerKey' );
delete_option( 'scoopit_consumerSecret' );
delete_option( 'scoopit_account' );
delete_option( 'scoopit_topic' );
delete_option( 'scoopit_recurrence' );
delete_option( 'scoopit_post_type' );
delete_option( 'scoopit_post_author' );
delete_option( 'scoopit_post_status' );
delete_option('scoopit_oauth_requestToken');
delete_option('scoopit_oauth_accessToken');
delete_option('scoopit_oauth_verifier');
delete_option('scoopit_oauth_verifier');
delete_option('scoopit_oauth_secret');
