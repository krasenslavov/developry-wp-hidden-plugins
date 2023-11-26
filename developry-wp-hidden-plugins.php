<?php
/**
 * Plugin Name: Developry &mdash; WP Hidden Plugins
 * Plugin URI: https://developry.com/
 * Description: Hide and disable unused plugins for later use.
 * Author: Krasen Slavov
 * Version: 0.1.2
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

/**
 * Add action links when plugin is activated to run the backups.
 */
add_action( 'plugin_action_links', __NAMESPACE__ . '\dwpe_action_links', 10, 2 );

function dwpe_action_links( $links, $file_path ) {
	if ( DWPHP_PLUGIN_BASENAME === $file_path ) {
		return $links;
	}

	ob_start();
	?>
	<select onchange="window.open(this.options[this.selectedIndex].value, '_blank').focus()" style="min-height: 16px; font-size: 13px;">
		<option value="">-- select one --</option>
		<option value="<?php echo esc_url( admin_url( "plugins.php?path={$file_path}dwphp=enable" ) ); ?>">Enable</option>
		<option value="<?php echo esc_url( admin_url( "plugins.php?path={$file_path}dwphp=disable" ) ); ?>">Disable</option>
	</select>
	<?php
	$links['dwphp'] = ob_get_contents();
	ob_end_clean();

	return $links;
}
