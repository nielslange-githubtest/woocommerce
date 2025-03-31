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
use WP_REST_Response;

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
		add_filter( 'rest_pre_serve_request', array( $this, 'serve_binary_file' ), 10, 4 );
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
	public function get_preview_permissions_check( \WP_REST_Request $request ): bool|\WP_Error {
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
	public function get_preview( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$attachment_id  = (int) $request['attachment_id'];
		$product_id     = (int) $request['product_id'];
		$requested_size = (string) ($request['size'] ?? 'large');

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

		// Create a response with file data to be served by serve_binary_file
		$response = new WP_REST_Response();

		// Store the file path and mime type in the response data
		$response->set_data(
			array(
				'file_path' => $file_path,
				'mime_type' => $mime_type,
			)
		);

		// Set appropriate headers for the file
		$response->set_headers(
			array(
				'Content-Type'        => $mime_type,
				'Content-Disposition' => 'inline; filename="' . basename( $file_path ) . '"',
				'Content-Length'      => filesize( $file_path ),
				'Cache-Control'       => 'no-cache, must-revalidate, max-age=0',
				'Pragma'              => 'no-cache',
				'Expires'             => 'Wed, 11 Jan 1984 05:00:00 GMT',
			)
		);

		return $response;
	}

	/**
	 * Pre-serve hook for handling binary file data
	 *
	 * @since 9.9.0
	 * @param bool             $served Whether the request has already been served.
	 * @param WP_HTTP_Response $result Result to send to the client.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @param WP_REST_Server   $server Server instance.
	 * @return bool True if the request was served, false otherwise.
	 */
	public function serve_binary_file( bool $served, $result, $request, $server ): bool {
		if ( $served ) {
			return true;
		}

		// Only handle our specific endpoint
		if ( 0 !== strpos( $request->get_route(), '/wc/v3/admin/product-downloads-preview/' ) ) {
			return false;
		}

		$data = $result->get_data();
		if ( empty( $data['file_path'] ) ) {
			return false;
		}

		$file_path = $data['file_path'];

		// Clean all output buffers
		while ( ob_get_level() ) {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Send headers from the response
		foreach ( $result->get_headers() as $key => $value ) {
			header( "{$key}: {$value}" );
		}

		// Send the file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );

		return true;
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
