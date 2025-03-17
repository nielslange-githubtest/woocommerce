<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

/**
 * Process install options for plugins.
 */
class ProcessCoreProfilerPluginInstallOptions {
	/**
	 * List of plugins.
	 *
	 * @var array List of plugins
	 */
	private array $plugins;

	/**
	 * Plugin slug.
	 *
	 * @var string Plugin slug
	 */
	private string $slug;

	/**
	 * Constructor.
	 *
	 * @param array  $plugins List of plugins.
	 * @param string $slug Plugin slug.
	 */
	public function __construct( array $plugins, string $slug ) {
		$this->plugins = $plugins;
		$this->slug    = $slug;
	}

	/**
	 * Process `before` options.
	 *
	 * @return void
	 */
	public function process_before() {
		$this->process_install_options( fn( $option ) => $this->is_before_priority( $option ) );
	}

	/**
	 * Process `after` options.
	 *
	 * @return void
	 */
	public function process_after() {
		$this->process_install_options( fn( $option ) => $this->is_after_priority( $option ) );
	}

	/**
	 * Retrieve install options for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array|null Install options or null if not found.
	 */
	public function get_install_options( string $plugin_slug ): ?array {
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
	 * @param callable $filter Function to filter install options.
	 */
	private function process_install_options( callable $filter ) {
		$install_options = $this->get_install_options( $this->slug );
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
	protected function update_install_option( object $install_option ) {
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
			// In case of JSON error, return early.
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return;
			}
		}

		$this->update_option( $install_option->name, $install_option->value, $options->autoload ? 'yes' : 'no' );
	}

	/**
	 * Updates an option in the WordPress database.
	 *
	 * @param string $name Option name.
	 * @param mixed  $value Option value.
	 * @param string $autoload Autoload option.
	 *
	 * @return void
	 */
	protected function update_option( string $name, $value, $autoload = 'no' ) {
		update_option( $name, $value, $autoload );
	}

	/**
	 * Determines if an install option should be processed before installation.
	 *
	 * @param object $install_option Install option object.
	 * @return bool True if the option is not "after" priority.
	 */
	private function is_before_priority( object $install_option ): bool {
		if ( ! isset( $install_option->options ) || ! is_object( $install_option->options ) ) {
			return true; // Default to 'before' if options are missing.
		}
		return empty( $install_option->options->install_priority ) || 'after' !== $install_option->options->install_priority;
	}

	/**
	 * Determines if an install option should be processed after installation.
	 *
	 * @param object $install_option Install option object.
	 * @return bool True if the option has "after" priority.
	 */
	private function is_after_priority( object $install_option ): bool {
		if ( ! isset( $install_option->options ) || ! is_object( $install_option->options ) ) {
			return false; // Default to 'not after' if options are missing.
		}
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
