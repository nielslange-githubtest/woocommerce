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
			'token' => [
				'required' => true,
				'type' => 'string',
				'description' => 'Secure access token',
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
	 * Permission check for the REST API endpoint.
	 *
	 * @since 9.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 *
	 */
	public function get_preview_permissions_check( $request ) {
		$token = $request->get_param( 'token' );

		if ( empty( $token ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_token',
				__( 'Missing access token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Check if token exists in transient.
		$transient_key = 'wc_preview_token_' . $token;
		$stored = get_transient( $transient_key );

		if ( ! $stored ) {
			return new WP_Error(
				'woocommerce_rest_invalid_token',
				__( 'Invalid or expired access token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Verify token is for the right attachment and product.
		if ( $stored['attachment_id'] != $request->get_param( 'attachment_id' ) ||
			$stored['product_id'] != $request->get_param( 'product_id' ) ) {
			return new WP_Error(
				'woocommerce_rest_token_mismatch',
				__( 'Token does not match requested resource.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Verify token was created by an admin user, not externally
		if ( empty( $stored['admin_verified'] ) || empty( $stored['signature'] ) ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized_token',
				__( 'Unauthorized token source.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}
		
		// Verify the cryptographic signature to ensure token was created through our admin function
		$data_to_verify = $stored['attachment_id'] . '|' . $stored['product_id'];
		$expected_signature = hash_hmac('sha256', $data_to_verify, AUTH_KEY . SECURE_AUTH_SALT);
		
		if ( ! hash_equals( $expected_signature, $stored['signature'] ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_signature',
				__( 'Invalid token signature.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Delete the transient to prevent reuse.
		delete_transient( $transient_key );

		return true;
	}

	/**
	 * REST API endpoint callback to serve the preview image.
	 *
	 * @since 9.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preview( $request ) {
		$attachment_id = $request['attachment_id'];
		$product_id = $request['product_id'];
		$requested_size = $request['size'] ?? 'large';

		// Get file path.
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! is_readable( $file_path ) ) {
			return new WP_Error(
				'woocommerce_rest_file_not_found',
				__( 'File not found', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// Only allow image mime types.
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

		// Handle image size requests - use 'large' as maximum size, 'full' can be too large.
		$size = $requested_size === 'full' ? 'large' : $requested_size;

		$resized = image_get_intermediate_size( $attachment_id, $size );
		if ( $resized && isset( $resized['path'] ) ) {
			$uploads_dir = wp_upload_dir();
			$resized_file_path = $uploads_dir['basedir'] . '/' . $resized['path'];
			if ( is_readable( $resized_file_path ) ) {
				$file_path = $resized_file_path;
			}
		}

		// Serve the file with appropriate headers.
		$this->clean_buffers();

		// We need to manually build the response to send proper binary data.
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: inline; filename="' . basename( $file_path ) . '"' );

		readfile( $file_path );
		exit;
	}

	/**
	 * Get secure URL for admin image that works with image src attributes
	 *
	 * @since 9.9.0
	 *
	 * @param int    $product_id    Product ID.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 * @return string Secure admin image URL.
	 *
	 */
	public function get_admin_image_src_url( $product_id, $attachment_id, $size ) {
		// Exit early if not an admin
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}

		// Record that an admin with proper permissions generated this token
		
		// Generate a secure token.
		$token = wp_generate_password( 32, false );

		// We're creating a token with a signature that can only be verified 
		// with access to this WordPress installation's secure keys

		// Create a signature using WordPress site keys and resource identifiers
		$data_to_sign = $attachment_id . '|' . $product_id;
		$signature = hash_hmac('sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT);
		
		// Store token in transient with 5-minute expiration
		$transient_key = 'wc_preview_token_' . $token;
		$token_data = [
			'attachment_id' => $attachment_id,
			'product_id' => $product_id,
			'admin_verified' => true,    // Indicates token was created through our admin function
			'signature' => $signature,   // Cryptographic signature for verification
		];

		set_transient( $transient_key, $token_data, 5 * MINUTE_IN_SECONDS );

		// Generate URL with token.
		$url = rest_url( "wc/v3/admin/product-downloads-preview/{$product_id}/{$attachment_id}" );
		$url = add_query_arg(
			[
				'size' => $size,
				'token' => $token,
			],
			$url
		);

		return $url;
	}

	/**
	 * Clean all output buffers.
	 *
	 * @since 9.9.0
	 *
	 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
	 */
	private function clean_buffers() {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} else {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
}
