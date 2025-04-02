<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Automattic\WooCommerce\Blocks\Domain\Services\AddressAutocomplete;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Tests for Address Autocomplete functionality
 */
class AddressAutocompleteTest extends MockeryTestCase {

	/**
	 * System under test.
	 *
	 * @var AddressAutocomplete
	 */
	private $sut;

	/**
	 * Setup test case.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->sut = Package::container()->get( AddressAutocomplete::class );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Tear down test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Get all registered providers and deregister them.
		$providers = $this->sut->get_registered_providers();

		foreach ( array_keys( $providers ) as $provider_id ) {
			__experimental_woocommerce_deregister_address_autocomplete_provider( $provider_id );
		}

		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		remove_all_actions( 'doing_it_wrong_run' );
	}

	/**
	 * Test registering a valid provider
	 */
	public function test_register_valid_provider() {
		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Test Provider' );
		$this->assertTrue( $result );

		$this->assertTrue( $this->sut->is_provider_available( 'test-provider' ) );
	}

	/**
	 * Test registering a provider with empty ID.
	 */
	public function test_register_provider_empty_id() {
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_register_address_autocomplete_provider',
				'Unable to register provider. The provider ID is required.',
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		$result = __experimental_woocommerce_register_address_autocomplete_provider( '', 'Test Provider' );
		$this->assertFalse( $result );
	}

	/**
	 * Test registering a provider with empty name.
	 */
	public function test_register_provider_empty_name() {
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_register_address_autocomplete_provider',
				esc_html( sprintf( 'Unable to register provider with id: "%s". The provider name is required.', 'test-provider' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', '' );
		$this->assertFalse( $result );
	}

	/**
	 * Test registering duplicate provider.
	 */
	public function test_register_duplicate_provider() {
		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Test Provider' );
		$this->assertTrue( $result );

		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->withArgs(
			array(
				'__experimental_woocommerce_register_address_autocomplete_provider',
				esc_html( sprintf( 'Unable to register provider with id: "%s". The provider is already registered.', 'test-provider' ) ),
			)
		)->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Another Provider' );
		$this->assertFalse( $result );

		__experimental_woocommerce_deregister_address_autocomplete_provider( 'test-provider' );
	}

	/**
	 * Test registration before blocks loaded.
	 */
	public function test_register_provider_before_blocks_loaded() {
		// Reset the blocks loaded state.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This is a test case where we need to simulate the action not firing.
		$GLOBALS['wp_actions']['woocommerce_blocks_loaded'] = 0;

		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Test Provider' );
		$this->assertFalse( $result ); // Should return false initially.

		// Trigger blocks loaded.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_blocks_loaded' );

		// Now check that the provider is registered.
		$this->assertTrue( $this->sut->is_provider_available( 'test-provider' ) );
	}

	/**
	 * Test getting registered providers.
	 */
	public function test_get_providers() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-1', 'Provider One' );
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-2', 'Provider Two' );
		$providers = $this->sut->get_registered_providers();

		$this->assertCount( 2, $providers );
		$this->assertArrayHasKey( 'provider-1', $providers );
		$this->assertArrayHasKey( 'provider-2', $providers );
		$this->assertEquals( 'Provider One', $providers['provider-1']['name'] );
		$this->assertEquals( 'Provider Two', $providers['provider-2']['name'] );
	}

	/**
	 * Test deregistering a non-existent provider.
	 */
	public function test_deregister_nonexistent_provider() {
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->once();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		$result = $this->sut->deregister_provider( 'nonexistent-provider' );
		$this->assertFalse( $result );
	}

	/**
	 * Test successful registration and deregistration.
	 */
	public function test_successful_register_and_deregister() {
		$doing_it_wrong_mocker = \Mockery::mock( 'ActionCallback' );
		$doing_it_wrong_mocker->shouldReceive( 'doing_it_wrong_run' )->never();

		add_action(
			'doing_it_wrong_run',
			array(
				$doing_it_wrong_mocker,
				'doing_it_wrong_run',
			),
			10,
			2
		);

		// Test registration.
		$result = __experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Test Provider' );
		$this->assertTrue( $result );

		// Verify provider is available.
		$this->assertTrue( $this->sut->is_provider_available( 'test-provider' ) );

		// Test deregistration.
		__experimental_woocommerce_deregister_address_autocomplete_provider( 'test-provider' );
		$this->assertFalse( $this->sut->is_provider_available( 'test-provider' ) );
	}

	/**
	 * Test settings when no providers are registered.
	 */
	public function test_add_address_autocomplete_settings_no_providers() {
		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the autocomplete settings.
		$autocomplete_enabled_setting  = null;
		$autocomplete_provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
				$autocomplete_enabled_setting = $setting;
				break;
			}
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$autocomplete_provider_setting = $setting;
				break;
			}
		}

		// Preferred provider should not be in the settings if no providers are registered.
		$this->assertNull( $autocomplete_provider_setting );


		$this->assertNotNull( $autocomplete_enabled_setting );
		$this->assertEquals( 'checkbox', $autocomplete_enabled_setting['type'] );
		$this->assertTrue( $autocomplete_enabled_setting['disabled'] );
		$this->assertStringContainsString( 'WooPayments', $autocomplete_enabled_setting['desc_tip'] );
	}

	/**
	 * Test settings when one provider is registered.
	 */
	public function test_add_address_autocomplete_settings_single_provider() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'test-provider', 'Test Provider' );

		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the autocomplete setting.
		$autocomplete_enabled_setting  = null;
		$autocomplete_provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
				$autocomplete_enabled_setting = $setting;
				break;
			}
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$autocomplete_provider_setting = $setting;
				break;
			}
		}

		// Preferred provider should not be in the settings if only one provider is registered.
		$this->assertNull( $autocomplete_provider_setting );

		$this->assertNotNull( $autocomplete_enabled_setting );
		$this->assertEquals( 'checkbox', $autocomplete_enabled_setting['type'] );
		$this->assertFalse( $autocomplete_enabled_setting['disabled'] );
		$this->assertStringNotContainsString( 'WooPayments', $autocomplete_enabled_setting['desc_tip'] );

		// Verify provider select is not added when only one provider exists.
		$provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$provider_setting = $setting;
				break;
			}
		}

		$this->assertNull( $provider_setting );
	}

	/**
	 * Test settings when multiple providers are registered.
	 */
	public function test_add_address_autocomplete_settings_multiple_providers() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-1', 'Provider One' );
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-2', 'Provider Two' );

		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the provider select setting.
		$provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$provider_setting = $setting;
				break;
			}
		}

		$this->assertNotNull( $provider_setting );
		$this->assertEquals( 'select', $provider_setting['type'] );
		$this->assertEquals( 'provider-1', $provider_setting['default'] );
		$this->assertArrayHasKey( 'provider-1', $provider_setting['options'] );
		$this->assertArrayHasKey( 'provider-2', $provider_setting['options'] );
		$this->assertEquals( 'Provider One', $provider_setting['options']['provider-1'] );
		$this->assertEquals( 'Provider Two', $provider_setting['options']['provider-2'] );
	}

	/**
	 * Test that settings are added in the correct position.
	 */
	public function test_add_address_autocomplete_settings_position() {
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the position of the default customer address setting and the autocomplete setting.
		$default_address_pos = -1;
		$autocomplete_pos    = -1;
		foreach ( $settings as $index => $setting ) {
			if ( isset( $setting['id'] ) ) {
				if ( 'woocommerce_default_customer_address' === $setting['id'] ) {
					$default_address_pos = $index;
				} elseif ( 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
					$autocomplete_pos = $index;
				}
			}
		}

		$this->assertGreaterThan( -1, $default_address_pos );
		$this->assertGreaterThan( -1, $autocomplete_pos );
		$this->assertGreaterThan( $default_address_pos, $autocomplete_pos );
		$this->assertEquals( $default_address_pos + 1, $autocomplete_pos );
	}

	/**
	 * Test getting preferred provider when set in options.
	 */
	public function test_get_preferred_provider_from_options() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-1', 'Provider One' );
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-2', 'Provider Two' );

		update_option( 'woocommerce_address_autocomplete_provider', 'provider-2' );

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-2', $preferred_provider );
	}

	/**
	 * Test getting preferred provider when option is not set.
	 */
	public function test_get_preferred_provider_fallback() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-1', 'Provider One' );
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-2', 'Provider Two' );

		delete_option( 'woocommerce_address_autocomplete_provider' );

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-1', $preferred_provider );
	}

	/**
	 * Test getting preferred provider when selected provider is no longer available.
	 */
	public function test_get_preferred_provider_unavailable() {
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-1', 'Provider One' );
		__experimental_woocommerce_register_address_autocomplete_provider( 'provider-2', 'Provider Two' );

		update_option( 'woocommerce_address_autocomplete_provider', 'provider-3' );

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-1', $preferred_provider );
	}

	/**
	 * Test getting preferred provider with no providers registered.
	 */
	public function test_get_preferred_provider_no_providers() {
		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEmpty( $preferred_provider );
	}
}
