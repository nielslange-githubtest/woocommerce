<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Cart\Utilities;

use Automattic\WooCommerce\Cart\Utilities\CartUtils;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variation;
use WC_Unit_Test_Case;

/**
 * Tests for the CartUtils class.
 */
class CartUtilsTest extends WC_Unit_Test_Case {

	/**
	 * Test generate_cart_id
	 */
	public function test_generate_cart_id() {
		$product_id     = 123;
		$variation_id   = 456;
		$variation      = array(
			'color' => 'red',
			'size'  => 'large',
		);
		$cart_item_data = array( 'custom_data' => 'test' );

		// Test with all parameters
		$cart_id1 = CartUtils::generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );
		$this->assertIsString( $cart_id1 );
		$this->assertEquals( 32, strlen( $cart_id1 ) ); // MD5 hash is 32 characters

		// Test with just product ID
		$cart_id2 = CartUtils::generate_cart_id( $product_id );
		$this->assertNotEquals( $cart_id1, $cart_id2 );

		// Test with same parameters should yield same ID
		$cart_id3 = CartUtils::generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );
		$this->assertEquals( $cart_id1, $cart_id3 );
	}

	/**
	 * Test get_cart_item_data_hash
	 */
	public function test_get_cart_item_data_hash() {
		// Create a simple product
		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Test Simple Product' );
		$simple_product->save();

		// Create a variation product
		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation' );
		$variation->set_parent_id( $simple_product->get_id() );
		$variation->set_attributes( array( 'color' => 'red' ) );
		$variation->save();

		// Test simple product
		$simple_hash = CartUtils::get_cart_item_data_hash( $simple_product );
		$this->assertIsString( $simple_hash );
		$this->assertEquals( 32, strlen( $simple_hash ) ); // MD5 hash is 32 characters

		// Test variation product
		$variation_hash = CartUtils::get_cart_item_data_hash( $variation );
		$this->assertIsString( $variation_hash );
		$this->assertEquals( 32, strlen( $variation_hash ) );

		// Different products should have different hashes
		$this->assertNotEquals( $simple_hash, $variation_hash );
	}

	/**
	 * Test format_stock_quantity_for_display
	 */
	public function test_format_stock_quantity_for_display() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->save();

		// Test with integer
		$result1 = CartUtils::format_stock_quantity_for_display( 10, $product );
		$this->assertEquals( '10', $result1 );

		// Test with float
		$result2 = CartUtils::format_stock_quantity_for_display( 10.5, $product );
		$this->assertEquals( '10.5', $result2 );

		// Test with zero
		$result3 = CartUtils::format_stock_quantity_for_display( 0, $product );
		$this->assertEquals( '0', $result3 );

		// Test with custom filter
		add_filter(
			'woocommerce_format_stock_quantity',
			function ( $qty ) {
				return 'Custom: ' . $qty;
			}
		);
		$result4 = CartUtils::format_stock_quantity_for_display( 10, $product );
		$this->assertEquals( 'Custom: 10', $result4 );

		// Clean up
		remove_all_filters( 'woocommerce_format_stock_quantity' );
	}
}
