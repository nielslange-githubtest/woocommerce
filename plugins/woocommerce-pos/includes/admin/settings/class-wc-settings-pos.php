<?php
/**
 * WooCommerce POS Settings
 *
 * @package WooCommerce_POS\Admin\Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_POS', false ) ) {
    return new WC_Settings_POS();
}

/**
 * WC_Settings_POS.
 */
class WC_Settings_POS extends WC_Settings_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id    = 'pos';
        $this->label = __( 'Point of Sale', 'woocommerce-pos' );

        parent::__construct();
    }

    /**
     * Get settings array for the section.
     *
     * @param string $section_id Section ID.
     * @return array
     */
    protected function get_settings_for_section_core( $section_id ) {
        $settings = array(
            array(
                'title' => __( 'Store details', 'woocommerce-pos' ),
                'type'  => 'title',
                'desc'  => __( 'These details are shown in POS receipts.', 'woocommerce-pos' ),
                'id'    => 'pos_store_details',
            ),

            array(
                'title'    => __( 'Store name', 'woocommerce-pos' ),
                'desc'     => __( 'The name of your physical store', 'woocommerce-pos' ),
                'id'       => 'wc_pos_store_name',
                'default'  => WC_POS_Settings_Defaults::get_default_store_name(),
                'type'     => 'text',
                'css'      => 'min-width:300px;',
            ),

            array(
                'title'    => __( 'Address', 'woocommerce-pos' ),
                'desc'     => __( 'The physical address of your store', 'woocommerce-pos' ),
                'id'       => 'wc_pos_store_address',
                'default'  => WC_POS_Settings_Defaults::get_default_store_address(),
                'type'     => 'textarea',
                'css'      => 'min-width:300px; height: 100px;',
            ),

            array(
                'title'    => __( 'Phone', 'woocommerce-pos' ),
                'desc'     => __( 'Your store contact number', 'woocommerce-pos' ),
                'id'       => 'wc_pos_store_phone',
                'default'  => '',
                'type'     => 'text',
                'css'      => 'min-width:300px;',
            ),

            array(
                'title'    => __( 'Email', 'woocommerce-pos' ),
                'desc'     => __( 'Your store contact email', 'woocommerce-pos' ),
                'id'       => 'wc_pos_store_email',
                'default'  => WC_POS_Settings_Defaults::get_default_store_email(),
                'type'     => 'email',
                'css'      => 'min-width:300px;',
            ),

            array(
                'title'    => __( 'Refund & Returns Policy', 'woocommerce-pos' ),
                'desc'     => __( 'Brief statement that will appear on POS receipts.', 'woocommerce-pos' ),
                'id'       => 'wc_pos_refund_returns_policy',
                'default'  => '',
                'type'     => 'textarea',
                'css'      => 'min-width:300px; height: 100px;',
                'desc_tip' => true,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'pos_store_details',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'pos_options',
            ),
        );

        return apply_filters( 'woocommerce_pos_settings', $settings );
    }

    /**
     * Get own sections.
     *
     * @return array
     */
    protected function get_own_sections() {
        return array(
            '' => __( 'General', 'woocommerce-pos' ),
        );
    }
}

return new WC_Settings_POS(); 
