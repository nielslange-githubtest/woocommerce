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
	 * Test that register method adds the necessary action
	 */
	public function test_register_adds_actions() {
		$this->preview->register();
		$this->assertTrue( has_action( 'rest_api_init', array( $this->preview, 'register_rest_routes' ) ) > 0 );
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
		$this->assertStringContainsString( 'token=', $result );
	}

	/**
	 * Test permissions check fails with empty token
	 */
	public function test_get_preview_permissions_check_fails_with_empty_token() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', '' );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_missing_token', $response->get_error_code() );
	}

	/**
	 * Test permissions check fails with invalid token
	 */
	public function test_get_preview_permissions_check_fails_with_invalid_token() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', 'invalid_token' );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_invalid_token', $response->get_error_code() );
	}

	/**
	 * Test permissions check fails with mismatched resources
	 */
	public function test_get_preview_permissions_check_fails_with_mismatched_resources() {
		// Generate a token
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create a token for different product
		$different_product_id = $this->product_id + 1;
		$token = wp_generate_password( 32, false );
		set_transient(
			'wc_preview_token_' . $token,
			array(
				'attachment_id' => $this->attachment_id,
				'product_id'    => $different_product_id,
			),
			60
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', $token );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_token_mismatch', $response->get_error_code() );
	}

	/**
	 * Test permissions check passes with valid token
	 */
	public function test_get_preview_permissions_check_passes_with_valid_token() {
		// Generate a token
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Create signature the same way the class would
		$data_to_sign = $this->attachment_id . '|' . $this->product_id;
		$signature = hash_hmac('sha256', $data_to_sign, AUTH_KEY . SECURE_AUTH_SALT);
		
		$token = wp_generate_password( 32, false );
		set_transient(
			'wc_preview_token_' . $token,
			array(
				'attachment_id' => $this->attachment_id,
				'product_id'    => $this->product_id,
				'admin_verified' => true,
				'signature'     => $signature,
			),
			60
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', $token );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertTrue( $response );

		// Verify token was deleted after use
		$this->assertFalse( get_transient( 'wc_preview_token_' . $token ) );
	}

	/**
	 * Test permissions check fails with externally created token (missing admin_verified flag)
	 */
	public function test_get_preview_permissions_check_fails_with_externally_created_token() {
		// Create a token externally (without using the class method, simulating an attack)
		$token = wp_generate_password( 32, false );
		set_transient(
			'wc_preview_token_' . $token,
			array(
				'attachment_id' => $this->attachment_id,
				'product_id'    => $this->product_id,
				// Note: admin_verified flag is deliberately missing
			),
			60
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', $token );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_unauthorized_token', $response->get_error_code() );
	}

	/**
	 * Test permissions check fails with invalid signature
	 */
	public function test_get_preview_permissions_check_fails_with_invalid_signature() {
		// Create a token with invalid signature
		$token = wp_generate_password( 32, false );
		set_transient(
			'wc_preview_token_' . $token,
			array(
				'attachment_id' => $this->attachment_id,
				'product_id'    => $this->product_id,
				'admin_verified' => true,
				'signature'     => 'invalid_signature_value',
			),
			60
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/' . $this->attachment_id );
		$request->set_param( 'token', $token );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', $this->attachment_id );

		$response = $this->preview->get_preview_permissions_check( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_invalid_signature', $response->get_error_code() );
	}

	/**
	 * Test invalid file path error in get_preview
	 */
	public function test_get_preview_returns_error_for_invalid_file() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/admin/product-downloads-preview/' . $this->product_id . '/99999' );
		$request->set_param( 'product_id', $this->product_id );
		$request->set_param( 'attachment_id', 99999 ); // Non-existent attachment ID

		$response = $this->preview->get_preview( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'woocommerce_rest_file_not_found', $response->get_error_code() );
	}
}
