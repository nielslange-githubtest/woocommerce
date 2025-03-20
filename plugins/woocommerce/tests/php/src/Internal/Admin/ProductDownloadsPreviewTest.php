<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ProductDownloadsPreview;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for ProductDownloadsPreview class
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductDownloadsPreview
 */
class ProductDownloadsPreviewTest extends WC_Unit_Test_Case {
	/**
	 * The product downloads preview class instance
	 *
	 * @var ProductDownloadsPreview
	 */
	private $preview;

	/**
	 * Test product ID
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Test attachment ID
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->preview = new ProductDownloadsPreview();

		// Create a test attachment
		$this->attachment_id = $this->create_test_image();

		// Create a test product
		$product          = WC_Helper_Product::create_simple_product();
		$this->product_id = $product->get_id();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown(): void {
		// Clean up created product
		WC_Helper_Product::delete_product( $this->product_id );

		// Clean up attachment
		if ( $this->attachment_id ) {
			wp_delete_attachment( $this->attachment_id, true );
		}

		parent::tearDown();
	}

	/**
	 * Create a test image attachment
	 *
	 * @return int Attachment ID
	 */
	private function create_test_image() {
		// Create test image file
		$upload_dir = wp_upload_dir();
		$filename   = $upload_dir['path'] . '/test-image.jpg';

		// Create a simple JPG image
		$image = imagecreatetruecolor( 100, 100 );
		imagejpeg( $image, $filename );

		// Create attachment
		$attachment = array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Test Image',
			'post_content'   => '',
			'post_status'    => 'publish',
			'file'           => $filename,
		);

		$attachment_id = wp_insert_attachment( $attachment, $filename );

		// Update attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}

	/**
	 * Test get_admin_image_src_url returns empty string for non-admin users
	 */
	public function test_get_admin_image_src_url_returns_empty_for_non_admin() {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$result = $this->preview->get_admin_image_src_url( $this->product_id, $this->attachment_id, 'thumbnail' );

		$this->assertEmpty( $result );
	}

	/**
	 * Test get_admin_image_src_url returns properly formatted URL for admin users
	 */
	public function test_get_admin_image_src_url_returns_url_for_admin() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$result = $this->preview->get_admin_image_src_url( $this->product_id, $this->attachment_id, 'thumbnail' );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( $this->product_id, $result );
		$this->assertStringContainsString( $this->attachment_id, $result );
		$this->assertStringContainsString( 'size=thumbnail', $result );
		$this->assertStringContainsString( 'signature=', $result );
	}

	/**
	 * Test permissions check fails with empty signature
	 */
	public function test_get_preview_permissions_check_fails_with_empty_signature() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', '' );
		$request->set_param( 'size', 'thumbnail' );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_missing_signature', $response->get_error_code() );
	}

	/**
	 * Test permissions check fails with invalid signature
	 */
	public function test_get_preview_permissions_check_fails_with_invalid_signature() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', 'invalid_signature' );
		$request->set_param( 'size', 'thumbnail' );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_invalid_signature', $response->get_error_code() );
	}

	/**
	 * Test permissions check fails with mismatched resources in stored data
	 */
	public function test_get_preview_permissions_check_fails_with_mismatched_resources() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$different_product_id = $this->product_id + 1;
		$size                 = 'thumbnail';
		$cache_key            = "wc_preview_{$this->product_id}_{$this->attachment_id}_{$size}";

		// Create a cache entry with mismatched data
		wp_cache_add(
			$cache_key,
			array(
				'attachment_id'  => $this->attachment_id,
				'product_id'     => $different_product_id, // Mismatch here
				'size'           => $size,
				'admin_verified' => true,
			),
			'wc_preview_tokens',
			60
		);

		// Create a valid signature for this product and attachment
		$data_to_sign = $this->attachment_id . '|' . $this->product_id;
		$signature    = hash_hmac( 'sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT );

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', $signature );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );
		$request->set_param( 'size', $size );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_resource_mismatch', $response->get_error_code() );
	}

	/**
	 * Test permissions check passes with valid signature
	 */
	public function test_get_preview_permissions_check_passes_with_valid_signature() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$data_to_sign = $this->attachment_id . '|' . $this->product_id;
		$signature    = hash_hmac( 'sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT );

		$size    = 'thumbnail';
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', $signature );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );
		$request->set_param( 'size', $size );

		$response = $this->preview->get_preview_permissions_check( $request );
		$this->assertTrue( $response );
	}

	/**
	 * Test permissions check fails with externally created cache entry (missing admin_verified flag)
	 */
	public function test_get_preview_permissions_check_fails_with_externally_created_entry() {
		$data_to_sign = $this->attachment_id . '|' . $this->product_id;
		$signature    = hash_hmac( 'sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT );

		$size      = 'thumbnail';
		$cache_key = "wc_preview_{$this->product_id}_{$this->attachment_id}_{$size}";
		wp_cache_add(
			$cache_key,
			array(
				'attachment_id' => $this->attachment_id,
				'product_id'    => $this->product_id,
				'size'          => $size,
				// Note: admin_verified flag is deliberately missing
			),
			'wc_preview_tokens',
			60
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', $signature );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );
		$request->set_param( 'size', $size );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_unauthorized', $response->get_error_code() );
	}

	/**
	 * Test invalid file path error in get_preview
	 */
	public function test_get_preview_returns_error_for_invalid_file() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/99999' );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', 99999 ); // Non-existent attachment ID
		$request->set_param( 'size', 'thumbnail' );

		$response = $this->preview->get_preview( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_file_not_found', $response->get_error_code() );
	}

	/**
	 * Test that a signature cannot be used twice (one-time use)
	 */
	public function test_signature_can_only_be_used_once() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Generate a valid URL with signature
		$url = $this->preview->get_admin_image_src_url( $this->product_id, $this->attachment_id, 'thumbnail' );
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_args );

		// Extract the signature from the URL
		$signature = $query_args['signature'];
		$size = $query_args['size'];

		// First request should succeed
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'signature', $signature );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );
		$request->set_param( 'size', $size );

		// First use should succeed
		$this->assertTrue( $this->preview->get_preview_permissions_check( $request ) );

		// Second request with the same signature should fail with signature already used error
		$second_request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$second_request->set_param( 'signature', $signature );
		$second_request->set_param( 'product_id', $this->product_id );
		$second_request->set_param( 'attachment_id', $this->attachment_id );
		$second_request->set_param( 'size', $size );

		// Second attempt should fail because signature is now in the used signatures list
		$second_response = $this->preview->get_preview_permissions_check( $second_request );
		$this->assertInstanceOf( 'WP_Error', $second_response );
		$this->assertEquals( 'woocommerce_rest_signature_already_used', $second_response->get_error_code() );
	}
}
