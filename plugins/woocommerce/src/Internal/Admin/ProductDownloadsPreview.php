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
		$namespace = 'wc/v3';
		$route_base = '/admin/product-downloads-preview';
		$route_pattern = '/(?P<product_id>[\d]+)/(?P<attachment_id>[\d]+)';

		$args = [
			'product_id' => [
				'required' => true,
				'type' => 'integer',
				'description' => 'Product ID that the downloadable image belongs to',
			],
			'attachment_id' => [
				'required' => true,
				'type' => 'integer',
				'description' => 'Attachment ID to preview',
			],
			'size' => [
				'type' => 'string',
				'default' => 'large',
				'description' => 'Image size to display',
			],
			'signature' => [
				'required' => true,
				'type' => 'string',
				'description' => 'Secure access signature',
			],
		];

		register_rest_route(
			$namespace,
			$route_base . $route_pattern,
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_preview'],
				'permission_callback' => [$this, 'get_preview_permissions_check'],
				'args' => $args,
			]
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
		$signature = $request->get_param( 'signature' );

		if ( empty( $signature ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_signature',
				__( 'Missing signature.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		$attachment_id = $request->get_param( 'attachment_id' );
		$product_id = $request->get_param( 'product_id' );
		$size = $request->get_param( 'size' ) ?? 'large';

		// Verify signature
		$data_to_verify = $attachment_id . '|' . $product_id;
		$expected_signature = hash_hmac('sha256', $data_to_verify, AUTH_KEY . SECURE_AUTH_SALT);

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_signature',
				__( 'Invalid signature.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Check for cached entry
		$cache_key = "wc_preview_{$product_id}_{$attachment_id}_{$size}";
		$stored = wp_cache_get( $cache_key, 'wc_preview_tokens' );

		if ( ! $stored ) {
			// Create cache entry if not exists
			$token_data = [
				'attachment_id' => $attachment_id,
				'product_id' => $product_id,
				'size' => $size,
				'admin_verified' => true,
			];

			wp_cache_add( $cache_key, $token_data, 'wc_preview_tokens', 5 * MINUTE_IN_SECONDS );
		} else {
			// Verify stored data matches request
			if ( $stored['attachment_id'] != $request->get_param( 'attachment_id' ) ||
				$stored['product_id'] != $request->get_param( 'product_id' ) ) {
				return new WP_Error(
					'woocommerce_rest_resource_mismatch',
					__( 'Request does not match resource.', 'woocommerce' ),
					array( 'status' => 403 )
				);
			}

			if ( empty( $stored['admin_verified'] ) ) {
				return new WP_Error(
					'woocommerce_rest_unauthorized',
					__( 'Unauthorized access.', 'woocommerce' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * Serve the preview image
	 *
	 * @since 9.9.0
	 * @param \WP_REST_Request $request Request details
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preview( $request ) {
		$attachment_id = $request['attachment_id'];
		$product_id = $request['product_id'];
		$requested_size = $request['size'] ?? 'large';

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! is_readable( $file_path ) ) {
			return new WP_Error(
				'woocommerce_rest_file_not_found',
				__( 'File not found', 'woocommerce' ),
				array( 'status' => 404 )
			);
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
			return new WP_Error(
				'woocommerce_rest_invalid_file_type',
				__( 'Invalid file type', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$size = $requested_size === 'full' ? 'large' : $requested_size;

		$resized = image_get_intermediate_size( $attachment_id, $size );
		if ( $resized && isset( $resized['path'] ) ) {
			$uploads_dir = wp_upload_dir();
			$resized_file_path = $uploads_dir['basedir'] . '/' . $resized['path'];
			if ( is_readable( $resized_file_path ) ) {
				$file_path = $resized_file_path;
			}
		}

		// Clean any existing output
		ob_clean();

		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: inline; filename="' . basename( $file_path ) . '"' );

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

		// Generate signature based on product and attachment IDs
		$data_to_sign = $attachment_id . '|' . $product_id;
		$signature = hash_hmac('sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT);

		// Store metadata in cache with simple key (no signature in key)
		$cache_key = "wc_preview_{$product_id}_{$attachment_id}_{$size}";
		$token_data = [
			'attachment_id' => $attachment_id,
			'product_id' => $product_id,
			'size' => $size,
			'admin_verified' => true,
		];

		wp_cache_add( $cache_key, $token_data, 'wc_preview_tokens', 5 * MINUTE_IN_SECONDS );

		// Use signature as query parameter for verification
		$url = rest_url( "wc/v3/admin/product-downloads-preview/{$product_id}/{$attachment_id}" );
		$url = add_query_arg(
			[
				'size' => $size,
				'signature' => $signature,
			],
			$url
		);

		return $url;
	}
}
