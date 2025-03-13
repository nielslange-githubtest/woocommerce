<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\PluginsInstallLoggers\PluginsInstallLogger;

/**
 * Process install options for plugins.
 */
class ProcessInstallOptions implements PluginsInstallLogger {

	/**
	 * Logger instance.
	 *
	 * @var PluginsInstallLogger|null Logger instance.
	 */
	private ?PluginsInstallLogger $logger;

	/**
	 * List of plugin objects.
	 *
	 * @var array List of plugin objects.
	 */
	private array $plugins;

	/**
	 * Constructor to initialize logger and plugins.
	 *
	 * @param array                     $plugins List of plugins.
	 * @param PluginsInstallLogger|null $logger Logger instance.
	 */
	public function __construct( array $plugins, ?PluginsInstallLogger $logger = null ) {
		$this->logger  = $logger;
		$this->plugins = $plugins;
	}

	/**
	 * Called when a plugin is requested to be installed.
	 *
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @return void
	 */
	public function install_requested( string $plugin_slug ) {
		$this->logger && $this->logger->install_requested( $plugin_slug );
		$this->process_install_options( $plugin_slug, fn( $option ) => $this->is_not_after_priority( $option ) );
	}

	/**
	 * Called when a plugin is installed.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param int    $duration Duration in seconds.
	 *
	 * @return void
	 */
	public function installed( string $plugin_slug, int $duration ) {
		$this->logger && $this->logger->installed( $plugin_slug, $duration );
		$this->process_install_options( $plugin_slug, fn( $option ) => $this->is_after_priority( $option ) );
	}

	/**
	 * Called when a plugin is activated.
	 *
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @return void
	 */
	public function activated( string $plugin_slug ) {
		$this->logger && $this->logger->activated( $plugin_slug );
	}

	/**
	 * Called when an error occurred while installing a plugin.
	 *
	 * @param string      $plugin_slug Plugin slug.
	 * @param string|null $error_message Error message.
	 *
	 * @return void
	 */
	public function add_error( string $plugin_slug, ?string $error_message = null ) {
		$this->logger && $this->logger->add_error( $plugin_slug, $error_message );
	}

	/**
	 * Called when all plugins are processed.
	 *
	 * @param array $data Return data from install_plugins().
	 *
	 * @return void
	 */
	public function complete( $data = array() ) {
		$this->logger && $this->logger->complete( $data );
	}

	/**
	 * Retrieve install options for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array|null Install options or null if not found.
	 */
	protected function get_install_options( string $plugin_slug ): ?array {
		foreach ( $this->plugins as $plugin ) {
			if ( $this->matches_plugin_slug( $plugin, $plugin_slug ) ) {
				return $plugin->install_options ?? null;
			}
		}
		return null;
	}

	/**
	 * Process install options based on a filtering function.
	 *
	 * @param string   $plugin_slug Plugin slug.
	 * @param callable $filter Function to filter install options.
	 */
	private function process_install_options( string $plugin_slug, callable $filter ) {
		$install_options = $this->get_install_options( $plugin_slug );
		if ( ! $install_options ) {
			return;
		}

		$filtered_options = array_filter( $install_options, $filter );
		foreach ( $filtered_options as $install_option ) {
			$this->update_install_option( $install_option );
		}
	}

	/**
	 * Updates an install option in the WordPress database.
	 *
	 * @param object $install_option Install option object.
	 */
	private function update_install_option( object $install_option ) {
		$default_options = array(
			'force_array' => false,
			'autoload'    => false,
		);

		$options = $install_option->options ?? new \stdClass();
		foreach ( $default_options as $key => $value ) {
			if ( ! isset( $options->$key ) ) {
				$options->$key = $value;
			}
		}

		if ( $options->force_array ) {
			$install_option->value = json_decode( wp_json_encode( $install_option->value ), true );
		}

		update_option( $install_option->name, $install_option->value, $options->autoload ? 'yes' : 'no' );
	}

	/**
	 * Determines if an install option should be processed before installation.
	 *
	 * @param object $install_option Install option object.
	 * @return bool True if the option is not "after" priority.
	 */
	private function is_not_after_priority( object $install_option ): bool {
		return empty( $install_option->options->install_priority ) || 'after' !== $install_option->options->install_priority;
	}

	/**
	 * Determines if an install option should be processed after installation.
	 *
	 * @param object $install_option Install option object.
	 * @return bool True if the option has "after" priority.
	 */
	private function is_after_priority( object $install_option ): bool {
		return ! empty( $install_option->options->install_priority ) && 'after' === $install_option->options->install_priority;
	}

	/**
	 * Checks if the given plugin matches the provided slug.
	 *
	 * @param object $plugin Plugin object.
	 * @param string $plugin_slug Plugin slug.
	 * @return bool True if it matches, false otherwise.
	 */
	private function matches_plugin_slug( object $plugin, string $plugin_slug ): bool {
		return explode( ':', $plugin->key )[0] === $plugin_slug;
	}
}
