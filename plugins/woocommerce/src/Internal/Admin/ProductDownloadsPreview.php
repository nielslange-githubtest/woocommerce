<?php
/**
 * ProductDownloads AdminPreview class file.
 *
 * @package WooCommerce\Internal\ProductDownloads
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_REST_Server;
use WP_Error;

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
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes for admin product downloads preview.
	 *
	 * @since 9.9.0
	 */
	public function register_rest_routes() {
		$namespace     = 'wc/v3';
		$route_base    = '/admin/product-downloads-preview';
		$route_pattern = '/(?P<product_id>[\d]+)/(?P<attachment_id>[\d]+)';

		$args = array(
			'product_id'    => array(
				'required'    => true,
				'type'        => 'integer',
				'description' => 'Product ID that the downloadable image belongs to',
			),
			'attachment_id' => array(
				'required'    => true,
				'type'        => 'integer',
				'description' => 'Attachment ID to preview',
			),
			'size'          => array(
				'type'        => 'string',
				'default'     => 'large',
				'description' => 'Image size to display',
			),
		);

		register_rest_route(
			$namespace,
			$route_base . $route_pattern,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_preview' ),
				'permission_callback' => array( $this, 'get_preview_permissions_check' ),
				'args'                => $args,
			)
		);
	}

	/**
	 * Permission check for REST API endpoint.
	 *
	 * @since 9.9.0
	 * @param \WP_REST_Request $request Request details.
	 * @return bool|\WP_Error
	 */
	public function get_preview_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized',
				__( 'Unauthorized access.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Serve the preview image
	 *
	 * @since 9.9.0
	 * @param \WP_REST_Request $request Request details.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preview( $request ) {
		$attachment_id  = $request['attachment_id'];
		$product_id     = $request['product_id'];
		$requested_size = $request['size'] ?? 'large';

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! is_readable( $file_path ) ) {
			return new WP_Error(
				'woocommerce_rest_file_not_found',
				__( 'File not found', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$mime_type          = get_post_mime_type( $attachment_id );
		$allowed_mime_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/webp',
		);

		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_file_type',
				__( 'Invalid file type', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$size = 'full' === $requested_size ? 'large' : $requested_size;

		$resized = image_get_intermediate_size( $attachment_id, $size );
		if ( $resized && isset( $resized['path'] ) ) {
			$uploads_dir       = wp_upload_dir();
			$resized_file_path = $uploads_dir['basedir'] . '/' . $resized['path'];
			if ( is_readable( $resized_file_path ) ) {
				$file_path = $resized_file_path;
			}
		}

		// Clean any existing output.
		ob_clean();

		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: inline; filename="' . basename( $file_path ) . '"' );

		// We need to use readfile here for binary file output.
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
	public function get_admin_image_src_url( $product_id, $attachment_id, $size ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}

		$url = rest_url( "wc/v3/admin/product-downloads-preview/{$product_id}/{$attachment_id}" );
		$url = add_query_arg(
			array(
				'size'     => $size,
				'_wpnonce' => wp_create_nonce( 'wp_rest' ),
			),
			$url
		);

		return $url;
	}
}
