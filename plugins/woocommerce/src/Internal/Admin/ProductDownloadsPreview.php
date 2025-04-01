<?php
/**
 * ProductDownloads Preview class file.
 *
 * @package WooCommerce\Internal\FileHandlers
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling secure admin previews of downloadable product files
 * that would otherwise be inaccessible due to server security configurations.
 *
 * @since 9.9.0
 */
class ProductDownloadsPreview implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * @since 9.9.0
	 */
	public function register() {
		// Register the endpoint
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Process the request
		add_action( 'parse_request', array( $this, 'handle_preview_request' ), 0 );
	}

	/**
	 * Register the download preview endpoint rewrite rules
	 *
	 * @since 9.9.0
	 */
	public function add_endpoint() {
		add_rewrite_rule(
			'^wc/file/download-preview/([0-9]+)/([0-9]+)(?:/([^/]*))?/?$',
			'index.php?wc_product_id=$matches[1]&wc_attachment_id=$matches[2]&wc_preview_size=$matches[3]',
			'top'
		);
	}

	/**
	 * Add our custom query vars
	 *
	 * @since 9.9.0
	 * @param array $vars The original query variables.
	 * @return array The updated query variables.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc_product_id';
		$vars[] = 'wc_attachment_id';
		$vars[] = 'wc_preview_size';
		return $vars;
	}

	/**
	 * Handle the preview request
	 *
	 * @since 9.9.0
	 */
	public function handle_preview_request() {
		global $wp;

		// Check if this is our endpoint
		if ( ! isset( $wp->query_vars['wc_product_id'] ) || ! isset( $wp->query_vars['wc_attachment_id'] ) ) {
			return;
		}

		// Verify permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			status_header( 403 );
			die( esc_html__( 'Unauthorized access.', 'woocommerce' ) );
		}

		// Verify nonce
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_download_preview' ) ) {
			status_header( 403 );
			die( esc_html__( 'Invalid security token.', 'woocommerce' ) );
		}

		$product_id = (int) $wp->query_vars['wc_product_id'];
		$attachment_id = (int) $wp->query_vars['wc_attachment_id'];
		$size = ! empty( $wp->query_vars['wc_preview_size'] ) ?
			$wp->query_vars['wc_preview_size'] : 'large';

		$this->serve_preview_file( $product_id, $attachment_id, $size );
	}

	/**
	 * Serve the preview file
	 *
	 * @since 9.9.0
	 * @param int    $product_id    Product ID.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size to display.
	 */
	private function serve_preview_file( int $product_id, int $attachment_id, string $size ) {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! is_readable( $file_path ) ) {
			status_header( 404 );
			die( esc_html__( 'File not found', 'woocommerce' ) );
		}

		$mime_type = get_post_mime_type( $attachment_id );
		$allowed_mime_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/webp',
		);

		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			status_header( 403 );
			die( esc_html__( 'Invalid file type', 'woocommerce' ) );
		}

		// Handle resized images
		$size = 'full' === $size ? 'large' : $size;
		$resized = image_get_intermediate_size( $attachment_id, $size );

		if ( $resized && isset( $resized['path'] ) ) {
			$uploads_dir = wp_upload_dir();
			$resized_file_path = $uploads_dir['basedir'] . '/' . $resized['path'];

			if ( is_readable( $resized_file_path ) ) {
				$file_path = $resized_file_path;
			}
		}

		// Clean all output buffers
		while ( ob_get_level() ) {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Send headers
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: inline; filename="' . basename( $file_path ) . '"' );

		// Output file and exit
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );
		exit;
	}

	/**
	 * Get secure URL for admin image
	 *
	 * @since 9.9.0
	 * @param int    $product_id    Product ID.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 * @return string Secure admin image URL.
	 */
	public function get_admin_image_src_url( int $product_id, int $attachment_id, string $size ): string {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}

		$url = home_url( "wc/file/download-preview/{$product_id}/{$attachment_id}" );

		if ( $size && 'large' !== $size ) {
			$url .= "/{$size}";
		}

		return add_query_arg(
			array(
				'_wpnonce' => wp_create_nonce( 'wc_download_preview' ),
			),
			$url
		);
	}
}
