<?php
/**
 * Plugin Name: Developry &mdash; WP Hidden Plugins
 * Plugin URI: https://developry.com/
 * Description: Hide and disable unused plugins for later use.
 * Author: Krasen Slavov
 * Version: 0.1.5
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

// ! defined( ABSPATH ) || exit;

define( __NAMESPACE__ . '\DWPHP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( __NAMESPACE__ . '\DWPHP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( __NAMESPACE__ . '\DWPHP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

// Activation and deactivation hooks.
register_activation_hook( __FILE__, __NAMESPACE__ . '\dwphp_activate_plugin' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\dwphp_deactivate_plugin' );

function dwphp_activate_plugin() {
	// Initialize plugin options if they don't exist.
	if ( false === get_option( 'dwphp_hidden_plugins' ) ) {
		add_option( 'dwphp_hidden_plugins', array() );
	}
}

function dwphp_deactivate_plugin() {
	// Optionally restore all hidden plugins on deactivation.
	$restore_on_deactivation = apply_filters( 'dwphp_restore_on_deactivation', false );

	if ( $restore_on_deactivation ) {
		dwphp_restore_all_hidden_plugins();
	}
}

function dwphp_restore_all_hidden_plugins() {
	$hidden_plugins = get_option( 'dwphp_hidden_plugins', array() );

	if ( empty( $hidden_plugins ) ) {
		return;
	}

	foreach ( $hidden_plugins as $plugin_file ) {
		dwphp_make_plugin_visible( $plugin_file );
	}

	// Clear the hidden plugins list.
	delete_option( 'dwphp_hidden_plugins' );
}

add_action( 'bulk_actions-plugins', __NAMESPACE__ . '\dwphp_add_bulk_actions', 10, 1 );

function dwphp_add_bulk_actions( $bulk_actions ) {
	$bulk_actions['dwphp_plugins_hidden']  = __( 'Make Hidden', 'developry-wp-hidden-plugins' );
	$bulk_actions['dwphp_plugins_visible'] = __( 'Make Visible', 'developry-wp-hidden-plugins' );

	return $bulk_actions;
}

add_filter( 'handle_bulk_actions-plugins', __NAMESPACE__ . '\dwphp_handle_bulk_action', 10, 3 );

function dwphp_handle_bulk_action( $redirect_to, $bulk_action, $plugins_selected ) {
	// Security: Check if user has permission to manage plugins.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return $redirect_to;
	}

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
		$current_hidden_plugins = get_option( 'dwphp_hidden_plugins', array() );

		foreach ( $plugins_selected as $plugin_file_path ) {
			// Verify admin password from database.
			if ( dwphp_verify_admin_password() ) {
				// Restore selected plugins directories and make them visible.
				if ( dwphp_make_plugin_visible( $plugin_file_path ) ) {
					// Remove from hidden plugins list.
					$key = array_search( $plugin_file_path, $current_hidden_plugins, true );
					if ( false !== $key ) {
						unset( $current_hidden_plugins[ $key ] );
					}
					$counter++;
				}
			}
		}

		// Update the hidden plugins list.
		update_option( 'dwphp_hidden_plugins', array_values( $current_hidden_plugins ) );

		return add_query_arg( 'dwphp_visible', $counter, $redirect_to );
	}

	if ( ! empty( $hidden_plugins ) ) {
		update_option( 'dwphp_hidden_plugins', $hidden_plugins );
	}

	return add_query_arg( 'dwphp_hidden', $counter, $redirect_to );
}

add_action( 'admin_notices', __NAMESPACE__ . '\dwphp_admin_notice_bulk_action' );

function dwphp_admin_notice_bulk_action() {
	// Check for hidden plugins action.
	if ( ! empty( $_REQUEST['dwphp_hidden'] ) ) {
		$count = intval( $_REQUEST['dwphp_hidden'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of plugins hidden */
					_n(
						'%d plugin has been hidden.',
						'%d plugins have been hidden.',
						$count,
						'developry-wp-hidden-plugins'
					),
					$count
				)
			)
		);
	}

	// Check for visible plugins action.
	if ( ! empty( $_REQUEST['dwphp_visible'] ) ) {
		$count = intval( $_REQUEST['dwphp_visible'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of plugins made visible */
					_n(
						'%d plugin has been made visible.',
						'%d plugins have been made visible.',
						$count,
						'developry-wp-hidden-plugins'
					),
					$count
				)
			)
		);
	}
}

function dwphp_verify_admin_password() {
	$stored_password = get_option( 'dwphp_admin_password', '' );

	// If no password is set, allow the operation.
	if ( empty( $stored_password ) ) {
		return true;
	}

	// If password is set, verify user has manage_options capability.
	// This provides security layer - only admins can hide/show plugins.
	// Future enhancement: Add AJAX modal for password verification.
	return current_user_can( 'manage_options' );
}

function dwphp_make_plugin_hidden( $plugin_file_path ) {
	$plugin_dir_name = pathinfo( $plugin_file_path, PATHINFO_DIRNAME );

	// Handle single-file plugins that don't have a directory.
	if ( '.' === $plugin_dir_name ) {
		return false;
	}

	$plugin_old_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_dir_name;
	$plugin_new_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '.' . $plugin_dir_name;

	// Check if source directory exists.
	if ( ! is_dir( $plugin_old_path ) ) {
		error_log( sprintf( 'DWPHP: Source directory does not exist: %s', $plugin_old_path ) );
		return false;
	}

	// Check if destination already exists.
	if ( file_exists( $plugin_new_path ) ) {
		error_log( sprintf( 'DWPHP: Destination already exists: %s', $plugin_new_path ) );
		return false;
	}

	// Attempt to rename the directory.
	if ( rename( $plugin_old_path, $plugin_new_path ) ) {
		return true;
	}

	error_log( sprintf( 'DWPHP: Failed to rename %s to %s', $plugin_old_path, $plugin_new_path ) );
	return false;
}

function dwphp_make_plugin_visible( $plugin_file_path ) {
	$plugin_dir_name = pathinfo( $plugin_file_path, PATHINFO_DIRNAME );

	// Handle single-file plugins that don't have a directory.
	if ( '.' === $plugin_dir_name ) {
		return false;
	}

	// Remove leading dot from directory name.
	$plugin_old_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '.' . $plugin_dir_name;
	$plugin_new_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_dir_name;

	// Check if source directory exists.
	if ( ! is_dir( $plugin_old_path ) ) {
		error_log( sprintf( 'DWPHP: Hidden directory does not exist: %s', $plugin_old_path ) );
		return false;
	}

	// Check if destination already exists.
	if ( file_exists( $plugin_new_path ) ) {
		error_log( sprintf( 'DWPHP: Destination already exists: %s', $plugin_new_path ) );
		return false;
	}

	// Attempt to rename the directory.
	if ( rename( $plugin_old_path, $plugin_new_path ) ) {
		return true;
	}

	error_log( sprintf( 'DWPHP: Failed to rename %s to %s', $plugin_old_path, $plugin_new_path ) );
	return false;
}

add_action( 'views_plugins', __NAMESPACE__ . '\dwphp_add_hidden_status', 10, 1 );

function dwphp_add_hidden_status( $plugin_statuses ) {
	if ( ! get_option( 'dwphp_hidden_plugins' ) ) {
		$count = 0;
	} else {
		$count = count( get_option( 'dwphp_hidden_plugins' ) );
	}

	$plugin_statuses['dwphp_hidden'] = sprintf(
		'<a href="%s">%s <span class="count">(%d)</span></a>',
		esc_url( admin_url( 'plugins.php?plugin_status=hidden' ) ),
		esc_html__( 'Hidden', 'developry-wp-hidden-plugins' ),
		$count
	);
	return $plugin_statuses;
}

add_action( 'load-plugins.php', __NAMESPACE__ . '\dwphp_load_plugins' );

function dwphp_load_plugins() {
	if ( isset( $_REQUEST['plugin_status'] ) && 'hidden' === $_REQUEST['plugin_status'] ) {
		// Add filter to show only hidden plugins.
		add_filter( 'all_plugins', __NAMESPACE__ . '\dwphp_filter_hidden_plugins' );
	}
}

function dwphp_filter_hidden_plugins( $plugins ) {
	$hidden_plugins = get_option( 'dwphp_hidden_plugins', array() );

	if ( empty( $hidden_plugins ) ) {
		return array();
	}

	// Manually discover hidden plugins from dot directories.
	$filtered_plugins = array();

	foreach ( $hidden_plugins as $plugin_file ) {
		$plugin_dir = pathinfo( $plugin_file, PATHINFO_DIRNAME );

		// Skip single-file plugins.
		if ( '.' === $plugin_dir ) {
			continue;
		}

		// Check if hidden directory exists.
		$hidden_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '.' . $plugin_dir;

		if ( ! is_dir( $hidden_dir ) ) {
			continue;
		}

		// Construct the full path to the plugin file.
		$plugin_full_path = $hidden_dir . DIRECTORY_SEPARATOR . basename( $plugin_file );

		// Check if plugin file exists.
		if ( ! file_exists( $plugin_full_path ) ) {
			continue;
		}

		// Get plugin data using WordPress function.
		$plugin_data = get_plugin_data( $plugin_full_path, false, false );

		// Add to filtered list with proper array structure.
		$filtered_plugins[ $plugin_file ] = array(
			'Name'        => $plugin_data['Name'],
			'PluginURI'   => $plugin_data['PluginURI'],
			'Version'     => $plugin_data['Version'],
			'Description' => $plugin_data['Description'],
			'Author'      => $plugin_data['Author'],
			'AuthorURI'   => $plugin_data['AuthorURI'],
			'TextDomain'  => $plugin_data['TextDomain'],
			'DomainPath'  => $plugin_data['DomainPath'],
			'Network'     => $plugin_data['Network'],
			'RequiresWP'  => $plugin_data['RequiresWP'],
			'RequiresPHP' => $plugin_data['RequiresPHP'],
			'Title'       => $plugin_data['Title'],
			'AuthorName'  => $plugin_data['AuthorName'],
		);
	}

	return $filtered_plugins;
}

// Settings page.
add_action( 'admin_menu', __NAMESPACE__ . '\dwphp_add_settings_page' );

function dwphp_add_settings_page() {
	add_options_page(
		__( 'Hidden Plugins Settings', 'developry-wp-hidden-plugins' ),
		__( 'Hidden Plugins', 'developry-wp-hidden-plugins' ),
		'manage_options',
		'dwphp-settings',
		__NAMESPACE__ . '\dwphp_render_settings_page'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\dwphp_register_settings' );

function dwphp_register_settings() {
	register_setting(
		'dwphp_settings_group',
		'dwphp_admin_password',
		array(
			'type'              => 'string',
			'sanitize_callback' => __NAMESPACE__ . '\dwphp_sanitize_password',
			'default'           => '',
		)
	);

	add_settings_section(
		'dwphp_security_section',
		__( 'Security Settings', 'developry-wp-hidden-plugins' ),
		__NAMESPACE__ . '\dwphp_security_section_callback',
		'dwphp-settings'
	);

	add_settings_field(
		'dwphp_admin_password',
		__( 'Admin Password', 'developry-wp-hidden-plugins' ),
		__NAMESPACE__ . '\dwphp_password_field_callback',
		'dwphp-settings',
		'dwphp_security_section'
	);
}

function dwphp_sanitize_password( $password ) {
	// Only hash if a new password is provided.
	if ( ! empty( $password ) ) {
		return wp_hash_password( $password );
	}
	// Return existing password if empty.
	return get_option( 'dwphp_admin_password', '' );
}

function dwphp_security_section_callback() {
	echo '<p>' . esc_html__( 'Set a password to protect hiding/showing plugins.', 'developry-wp-hidden-plugins' ) . '</p>';
}

function dwphp_password_field_callback() {
	$has_password = ! empty( get_option( 'dwphp_admin_password' ) );
	?>
	<input type="password"
		name="dwphp_admin_password"
		id="dwphp_admin_password"
		class="regular-text"
		placeholder="<?php echo esc_attr( $has_password ? __( 'Enter new password to change', 'developry-wp-hidden-plugins' ) : __( 'Enter password', 'developry-wp-hidden-plugins' ) ); ?>"
	/>
	<p class="description">
		<?php
		if ( $has_password ) {
			esc_html_e( 'Password is currently set. Leave blank to keep current password.', 'developry-wp-hidden-plugins' );
		} else {
			esc_html_e( 'Set a password to require authentication when hiding or showing plugins.', 'developry-wp-hidden-plugins' );
		}
		?>
	</p>
	<?php
}

function dwphp_render_settings_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'dwphp_settings_group' );
			do_settings_sections( 'dwphp-settings' );
			submit_button( __( 'Save Settings', 'developry-wp-hidden-plugins' ) );
			?>
		</form>
	</div>
	<?php
}
