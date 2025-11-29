<?php
/**
 * Class Test_Hidden_Plugins
 *
 * @package Developry_WP_Hidden_Plugins
 */

namespace DWPHP\Tests;

use WP_UnitTestCase;

/**
 * Test cases for hidden plugins functionality.
 */
class Test_Hidden_Plugins extends WP_UnitTestCase {

	/**
	 * Test that bulk actions are properly registered.
	 */
	public function test_bulk_actions_registered() {
		$bulk_actions = apply_filters( 'bulk_actions-plugins', array() );

		$this->assertArrayHasKey( 'dwphp_plugins_hidden', $bulk_actions );
		$this->assertArrayHasKey( 'dwphp_plugins_visible', $bulk_actions );
		$this->assertEquals( 'Make Hidden', $bulk_actions['dwphp_plugins_hidden'] );
		$this->assertEquals( 'Make Visible', $bulk_actions['dwphp_plugins_visible'] );
	}

	/**
	 * Test that hidden status is added to plugin views.
	 */
	public function test_hidden_status_added() {
		$plugin_statuses = apply_filters( 'views_plugins', array() );

		$this->assertArrayHasKey( 'dwphp_hidden', $plugin_statuses );
		$this->assertStringContainsString( 'Hidden', $plugin_statuses['dwphp_hidden'] );
	}

	/**
	 * Test password verification when no password is set.
	 */
	public function test_password_verification_no_password() {
		delete_option( 'dwphp_admin_password' );

		$result = \DWPHP\dwphp_verify_admin_password();

		$this->assertTrue( $result );
	}

	/**
	 * Test password verification with password set requires admin capability.
	 */
	public function test_password_verification_with_password() {
		update_option( 'dwphp_admin_password', wp_hash_password( 'test123' ) );

		// Create a user without manage_options capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$result = \DWPHP\dwphp_verify_admin_password();

		$this->assertFalse( $result );

		// Clean up.
		delete_option( 'dwphp_admin_password' );
	}

	/**
	 * Test bulk action handler with no capabilities.
	 */
	public function test_bulk_action_no_capability() {
		// Create a user without activate_plugins capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$redirect_to = admin_url( 'plugins.php' );
		$result      = \DWPHP\dwphp_handle_bulk_action( $redirect_to, 'dwphp_plugins_hidden', array() );

		// Should return unchanged redirect URL without adding query args.
		$this->assertEquals( $redirect_to, $result );
	}

	/**
	 * Test hidden plugins option storage.
	 */
	public function test_hidden_plugins_option() {
		$test_plugins = array( 'test-plugin/test-plugin.php', 'another-plugin/another.php' );

		update_option( 'dwphp_hidden_plugins', $test_plugins );

		$stored_plugins = get_option( 'dwphp_hidden_plugins' );

		$this->assertEquals( $test_plugins, $stored_plugins );

		// Clean up.
		delete_option( 'dwphp_hidden_plugins' );
	}

	/**
	 * Test settings registration.
	 */
	public function test_settings_registered() {
		global $wp_registered_settings;

		do_action( 'admin_init' );

		$this->assertArrayHasKey( 'dwphp_admin_password', $wp_registered_settings );
	}

	/**
	 * Test settings page added to admin menu.
	 */
	public function test_settings_page_added() {
		global $submenu;

		// Set current user as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		do_action( 'admin_menu' );

		// Check if settings page is registered under Settings menu.
		$this->assertArrayHasKey( 'options-general.php', $submenu );

		$found = false;
		foreach ( $submenu['options-general.php'] as $item ) {
			if ( isset( $item[2] ) && 'dwphp-settings' === $item[2] ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Settings page not found in admin menu' );
	}
}
