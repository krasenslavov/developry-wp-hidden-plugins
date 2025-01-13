<?php
/**
 * Plugin Name: Developry &mdash; WP Hidden Plugins
 * Plugin URI: https://developry.com/
 * Description: Hide and disable unused plugins for later use.
 * Author: Krasen Slavov
 * Version: 0.1.4
 * Author URI: https://krasenslavov.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: developry-wp-hidden-plugins
 * Domain Path: /lang
 *
 * GitHub Plugin URI: https://github.com/krasenslavov/developry-wp-hidden-plugins.git
 *
 * Copyright 2018 - 2023 Developry Ltd. (email: contact@developry.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace DWPHP;

! defined( ABSPATH ) || exit;

define( __NAMESPACE__ . '\DWPHP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( __NAMESPACE__ . '\DWPHP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( __NAMESPACE__ . '\DWPHP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'bulk_actions-plugins', __NAMESPACE__ . '\dwphp_add_bulk_actions', 10, 1 );

function dwphp_add_bulk_actions( $bulk_actions ) {
	$bulk_actions['dwphp_plugins_hidden']  = 'Make Hidden';
	$bulk_actions['dwphp_plugins_visible'] = 'Make Visible';

	return $bulk_actions;
}

add_filter( 'handle_bulk_actions-plugins', __NAMESPACE__ . '\dwphp_handle_bulk_action', 10, 3 );

function dwphp_handle_bulk_action( $redirect_to, $bulk_action, $plugins_selected ) {
	$hidden_plugins = array();
	$plugins_active = get_option( 'active_plugins' );
	$counter        = 0;

	if ( 'dwphp_plugins_hidden' === $bulk_action ) {
		foreach ( $plugins_selected as $plugin_file_path ) {
			// Skip all active plugins including our own plugin.
			if ( in_array( $plugin_file_path, $plugins_active, true ) || DWPHP_PLUGIN_BASENAME === $plugin_file_path ) {
				continue;
			} else {
				// Verify admin password from database.
				if ( dwphp_verify_admin_password() ) {
					// Rename selected plugins directories and make them hidden.
					if ( dwphp_make_plugin_hidden( $plugin_file_path ) ) {
						$hidden_plugins[] = $plugin_file_path;
						$counter++;
					}
				}
			}
		}
	}

	if ( 'dwphp_plugins_visible' === $bulk_action ) {
	}

	if ( ! empty( $hidden_plugins ) ) {
		update_option( 'dwphpa_hidden_plugins', $hidden_plugins );
	}

	return add_query_arg( 'dwphpa', $counter, $redirect_to );
}

add_action( 'admin_notices', __NAMESPACE__ . '\dwphp_admin_notice_bulk_action' );

function dwphp_admin_notice_bulk_action() {
}

function dwphp_verify_admin_password() {
	return true;
}

function dwphp_make_plugin_hidden( $plugin_file_path ) {
	$plugin_dir_name = pathinfo( $plugin_file_path, PATHINFO_DIRNAME );

	$plugin_old_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_dir_name;
	$plugin_new_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '.' . $plugin_dir_name;

	if ( @rename( $plugin_old_path, $plugin_new_path ) ) {
		return true;
	}

	return false;
}

add_action( 'views_plugins', __NAMESPACE__ . '\dwphp_add_hidden_status', 10, 1 );

function dwphp_add_hidden_status( $plugin_statuses ) {
	if ( ! get_option( 'dwphpa_hidden_plugins' ) ) {
		$count = 0;
	} else {
		$count = count( get_option( 'dwphpa_hidden_plugins' ) );
	}

	$plugin_statuses['dwphp_hidden'] = '<a href="' . admin_url( 'plugins.php' ) . '?plugin_status=hidden">Hidden <span class="count">(' . $count . ')</span></a>';
	return $plugin_statuses;
}

add_action( 'load-plugins.php', __NAMESPACE__ . '\dwphp_load_plugins' );

function dwphp_load_plugins() {
	if ( 'hidden' === $_REQUEST['plugin_status'] ) {

	}
}


/**
 * Add action links when plugin is activated to run the backups.
 */
// add_action( 'plugin_action_links', __NAMESPACE__ . '\dwpe_action_links', 10, 2 );

// function dwpe_action_links( $links, $file_path ) {
// 	$active_plugins = get_option( 'active_plugins' );

// 	if ( in_array( $file_path, $active_plugins, true ) ) {
// 		return $links;
// 	}

// 	if ( DWPHP_PLUGIN_BASENAME === $file_path ) {
// 		return $links;
// 	}

// 	ob_start();
// 	?>
// 	<select onchange="window.open(this.options[this.selectedIndex].value, '_blank').focus()" style="min-height: 16px; font-size: 13px;">
// 		<option value="">-- select one --</option>
// 		<option value="<?php echo esc_url( admin_url( "plugins.php?path={$file_path}dwphp=enable" ) ); ?>">Enable</option>
// 		<option value="<?php echo esc_url( admin_url( "plugins.php?path={$file_path}dwphp=disable" ) ); ?>">Disable</option>
// 	</select>
// 	<?php
// 	$links['dwphp'] = ob_get_contents();
// 	ob_end_clean();

// 	return $links;
// }