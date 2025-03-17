<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Mockery;
use WC_Unit_Test_Case;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\ProcessCoreProfilerPluginInstallOptions;

/**
 * Class ProcessCoreProfilerPluginInstallOptionsTest
 */
class ProcessCoreProfilerPluginInstallOptionsTest extends WC_Unit_Test_Case {

	/**
	 * Test that get_install_options returns the correct options.
	 * @return void
	 */
	public function test_get_install_options_returns_correct_options() {
		$plugins = array(
			(object) array(
				'key'             => 'test-plugin:some-extra',
				'install_options' => array( 'option1' => 'value1' ),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );

		$this->assertEquals( array( 'option1' => 'value1' ), $instance->get_install_options( 'test-plugin' ) );
	}

	/**
	 * Test that get_install_options returns null when the plugin is not found.
	 * @return void
	 */
	public function test_get_install_options_returns_null_when_plugin_not_found() {
		$plugins = array(
			(object) array(
				'key'             => 'different-plugin:extra',
				'install_options' => array( 'option1' => 'value1' ),
			),
		);

		$instance = new ProcessCoreProfilerPluginInstallOptions( $plugins, 'test-plugin' );

		$this->assertNull( $instance->get_install_options( 'test-plugin' ) );
	}

	/**
	 * Test that matches_plugin_slug returns true when the plugin slug matches.
	 * @return void
	 */
	public function test_matches_plugin_slug_returns_true_when_matching() {
		$plugin = (object) array( 'key' => 'my-plugin:extra-info' );

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'my-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'matches_plugin_slug' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $instance, $plugin, 'my-plugin' ) );
	}

	/**
	 * Test that matches_plugin_slug returns false when the plugin slug does not match.
	 * @return void
	 */
	public function test_matches_plugin_slug_returns_false_when_not_matching() {
		$plugin = (object) array( 'key' => 'different-plugin:extra-info' );

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'my-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'matches_plugin_slug' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $instance, $plugin, 'my-plugin' ) );
	}

	/**
	 * Test that process_install_options calls the correct method.
	 * @return void
	 */
	public function test_process_before_filters_correctly() {
		$install_options = array(
			(object) array( 'options' => (object) array( 'install_priority' => 'before' ) ),
			(object) array( 'options' => (object) array( 'install_priority' => 'after' ) ),
		);

		$plugins = array(
			(object) array(
				'key'             => 'my-plugin:extra',
				'install_options' => $install_options,
			),
		);

		$mock = Mockery::mock( ProcessCoreProfilerPluginInstallOptions::class, array( $plugins, 'my-plugin' ) )
						->makePartial()
						->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'update_install_option' )
			->once()
			->with( $install_options[0] );

		$mock->process_before(); // Ensure method is called.

		$this->assertTrue( true );
		Mockery::getContainer()->mockery_verify();
	}

	/**
	 * Test that process_after filters correctly.
	 * @return void
	 */
	public function test_process_after_filters_correctly() {
		$install_options = array(
			(object) array( 'options' => (object) array( 'install_priority' => 'before' ) ),
			(object) array( 'options' => (object) array( 'install_priority' => 'after' ) ),
		);

		$plugins = array(
			(object) array(
				'key'             => 'my-plugin:extra',
				'install_options' => $install_options,
			),
		);

		$mock = Mockery::mock( ProcessCoreProfilerPluginInstallOptions::class, array( $plugins, 'my-plugin' ) )
						->makePartial()
						->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'update_install_option' )
			->once()
			->with( $install_options[1] );

		$mock->process_after(); // Ensure method is called.

		$this->assertTrue( true );
		Mockery::getContainer()->mockery_verify();
	}

	/**
	 * Test that update_option calls the correct function.
	 * @return void
	 */
	public function test_update_option_calls_update_option_function() {
		$mock = Mockery::mock( ProcessCoreProfilerPluginInstallOptions::class )
						->makePartial()
						->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'update_option' )
			->once()
			->with( 'test_option', 'test_value', 'yes' );

		$reflection = new \ReflectionClass( $mock );
		$method     = $reflection->getMethod( 'update_option' );
		$method->setAccessible( true );

		$method->invoke( $mock, 'test_option', 'test_value', 'yes' );
		$this->assertTrue( true );
	}

	/**
	 * Test that is_before_priority returns true when the priority is 'before'.
	 * @return void
	 */
	public function test_is_before_priority_handles_missing_options() {
		$install_option = (object) array();

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'test-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'is_before_priority' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $instance, $install_option ) );
	}

	/**
	 * Test that is_after_priority returns false when the priority is 'after'.
	 * @return void
	 */
	public function test_is_after_priority_handles_missing_options() {
		$install_option = (object) array();

		$instance = new ProcessCoreProfilerPluginInstallOptions( array(), 'test-plugin' );

		$reflection = new \ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'is_after_priority' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $instance, $install_option ) );
	}
}
