<?php
/**
 * WooCommerce POS Settings Default Values
 *
 * @package WooCommerce_POS\Admin\Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Settings_Defaults Class.
 */
class WC_POS_Settings_Defaults {
    /**
     * Get default store email.
     *
     * @return string
     */
    public static function get_default_store_email() {
        return get_option('admin_email');
    }

    /**
     * Get default store name.
     *
     * @return string
     */
    public static function get_default_store_name() {
        return get_bloginfo('name');
    }

    /**
     * Get default store address.
     *
     * @return string
     */
    public static function get_default_store_address() {
        return wp_specialchars_decode(
            WC()->countries->get_formatted_address(
                array(
                    'address_1' => WC()->countries->get_base_address(),
                    'address_2' => WC()->countries->get_base_address_2(),
                    'city'      => WC()->countries->get_base_city(),
                    'state'     => WC()->countries->get_base_state(),
                    'postcode'  => WC()->countries->get_base_postcode(),
                    'country'   => WC()->countries->get_base_country(),                    
                ),
                "\n"
            )
        );
    }
}
