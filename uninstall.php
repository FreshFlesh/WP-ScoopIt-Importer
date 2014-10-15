<?php

if(!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();

wp_clear_scheduled_hook('scoopit_scheduled_hook');
delete_option( 'scoopitimporter.settings' );
delete_option( 'scoopitimporter.last_update' );
