<?php
/**
 * Uninstall script for Developry WP Hidden Plugins.
 *
 * This file is executed when the plugin is deleted from WordPress.
 *
 * @package Developry_WP_Hidden_Plugins
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Restore all hidden plugins before uninstalling.
$hidden_plugins = get_option( 'dwphp_hidden_plugins', array() );

if ( ! empty( $hidden_plugins ) ) {
	foreach ( $hidden_plugins as $plugin_file ) {
		$plugin_dir_name = pathinfo( $plugin_file, PATHINFO_DIRNAME );

		// Handle single-file plugins.
		if ( '.' === $plugin_dir_name ) {
			continue;
		}

		$plugin_old_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '.' . $plugin_dir_name;
		$plugin_new_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_dir_name;

		// Restore hidden plugin.
		if ( is_dir( $plugin_old_path ) && ! file_exists( $plugin_new_path ) ) {
			rename( $plugin_old_path, $plugin_new_path );
		}
	}
}

// Delete plugin options.
delete_option( 'dwphp_hidden_plugins' );
delete_option( 'dwphp_admin_password' );

// For multisite installations, delete options from all sites.
if ( is_multisite() ) {
	global $wpdb;

	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'dwphp_hidden_plugins' );
		delete_option( 'dwphp_admin_password' );
		restore_current_blog();
	}
}
