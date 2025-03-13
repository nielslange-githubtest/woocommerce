<?php
/**
 * WC_BIS_Settings class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

use Automattic\WooCommerce\Internal\BackInStockNotifications;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_BIS_Settings' ) ) :

	/**
	 * WooCommerce Back In Stock Notifications Settings.
	 *
	 * @class    WC_BIS_Settings
	 * @version  1.3.0
	 */
	class WC_BIS_Settings extends WC_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'bis_settings';
			$this->label = __( 'Stock Notifications', 'woocommerce' );

			// Add subtab to WC > Settings > Products.
			add_filter( 'woocommerce_get_sections_products', array( $this, 'add_bis_section_to_product_settings' ), 100, 1 );

			// Customer stock notifications subtab settings.
			add_filter( 'woocommerce_get_settings_products', array( $this, 'add_customer_stock_notifications_settings' ), 100, 2 );
		}

		public function add_bis_section_to_product_settings( array $sections ): array {
			// Add bis_settings section to products tab after Inventory section.
			$inventory_index = array_search( 'inventory', array_keys( $sections ) );
			if ( $inventory_index !== false ) {
				$sections = array_slice( $sections, 0, $inventory_index + 1, true ) +
					array( 'bis_settings' => __( 'Customer stock notifications', 'woocommerce' ) ) +
					array_slice( $sections, $inventory_index + 1, null, true );
			} else {
				$sections['bis_settings'] = __( 'Customer stock notifications', 'woocommerce' );
			}

			return $sections;
		}


		/**
		 * Handler for 'woocommerce_get_settings_products', adds the settings related to the Back In Stock Notifications.
		 *
		 * @param array  $settings Original settings configuration array.
		 * @param string $section_id Settings section identifier.
		 * @return array New settings configuration array.
		 *
		 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
		 */
		public function add_customer_stock_notifications_settings( array $settings, string $section_id ): array {
			if ( 'bis_settings' === $section_id ) {
				$title_item = array(
					'title' => __( 'Customer stock notifications', 'woocommerce' ),
					'type'  => 'title',
				);

				$settings[] = $title_item;

				$default_bis_settings = array(

					array(
						'title'   => __( 'Allow sign-ups', 'woocommerce' ),
						'desc'    => __( 'Let customers sign up to be notified when products in your store are restocked.', 'woocommerce' ),
						'id'      => 'wc_bis_allow_signups',
						'default' => 'yes',
						'type'    => 'checkbox',
					),

					array(
						'title'   => __( 'Require double opt-in to sign up', 'woocommerce' ),
						'desc'    => __( 'To complete the sign-up process, customers must follow a verification link sent to their e-mail after submitting the sign-up form.', 'woocommerce' ),
						'id'      => 'wc_bis_double_opt_in_required',
						'default' => 'no',
						'type'    => 'checkbox',
					),

					array(
						'title'   => __( 'Delete unverified notification sign-ups after (in days)', 'woocommerce' ),
						'desc'    => __( 'Contols how long the plugin will store unverified notification sign-ups in the database. Enter zero, or leave this field empty if you would like to store expired sign-up requests indefinitey.', 'woocommerce' ),
						'id'      => 'wc_bis_delete_unverified_days_threshold',
						'default' => 0,
						'type'    => 'number',
						'class'   => 'double_opt_in_required',
					),

					array(
						'title'    => __( 'Require account to sign up', 'woocommerce' ),
						'desc'     => __( 'Customers must be logged in to sign up for stock notifications.', 'woocommerce' ),
						'id'       => 'wc_bis_account_required',
						'default'  => 'no',
						'type'     => 'checkbox',
						'desc_tip' => __( 'When enabled, guests will be redirected to a login page to complete the sign-up process.', 'woocommerce' ),
					),

					array(
						'title'   => __( 'Create account on sign-up', 'woocommerce' ),
						'desc'    => __( 'Create an account when guests sign up for stock notifications.', 'woocommerce' ),
						'id'      => 'wc_bis_create_new_account_on_registration',
						'default' => 'no',
						'type'    => 'checkbox',
						'class'   => 'account_required_field',
					),

					array(
						'title'             => __( 'Minimum stock quantity', 'woocommerce' ),
						'desc'              => __( 'Stock quantity required to trigger stock notifications when restocking.', 'woocommerce' ),
						'id'                => 'wc_bis_stock_threshold',
						'default'           => 0,
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => 0,
							'step' => 1,
						),
					),

					array(
						'type' => 'sectionend',
						'id'   => 'bis_settings_general',
					),

					array(
						'title' => __( 'Product Page', 'woocommerce' ),
						'type'  => 'title',
						'id'    => 'bis_settings_products',
					),

					array(
						'title'   => __( 'Display opt-in checkbox', 'woocommerce' ),
						'desc'    => __( 'Enable this option if you would like guests to provide explicit consent in order to sign up.', 'woocommerce' ),
						'id'      => 'wc_bis_opt_in_required',
						'default' => 'no',
						'type'    => 'checkbox',
						'class'   => 'account_required_field',
					),

					array(
						'title'             => __( 'Opt-in checkbox text', 'woocommerce' ),
						'id'                => 'wc_bis_create_new_account_optin_text',
						'placeholder'       => wc_bis_get_form_privacy_default_text(),
						'default'           => wc_bis_get_form_privacy_default_text(),
						'type'              => 'textarea',
						'custom_attributes' => array(
							'rows' => 5,
						),
						'class'             => 'opt_in_required',
					),

					array(
						'title'    => __( 'Display signed-up customers', 'woocommerce' ),
						'desc'     => __( 'Let visitors know how many customers have already signed up.', 'woocommerce' ),
						'id'       => 'wc_bis_show_product_registrations_count',
						'default'  => 'no',
						'desc_tip' => __( 'Note: If page caching is enabled on your site, the displayed count may not be accurate at all times.', 'woocommerce' ),
						'type'     => 'checkbox',
					),

					array(
						'title'       => __( 'Signed-up customers text', 'woocommerce' ),
						'id'          => 'wc_bis_product_registrations_text',
						'placeholder' => wc_bis_get_form_signups_count_default_text(),
						'default'     => wc_bis_get_form_signups_count_default_text(),
						'desc'        => __( 'Text to use when 1 customer has signed up for a stock notification.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'product_registrations_text',
					),

					array(
						'title'       => '',
						'id'          => 'wc_bis_product_registrations_plural_text',
						/* translators: customers_count */
						'placeholder' => wc_bis_get_form_signups_count_plural_default_text(),
						'default'     => wc_bis_get_form_signups_count_plural_default_text(),
						/* translators: customers_count */
						'desc'        => __( 'Text to use when multiple customers have signed up for stock notifications. <code>{customers_count}</code> will be substituted by the number of signed-up customers.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'product_registrations_text',
					),

					array(
						'title'       => __( 'Sign-up form text', 'woocommerce' ),
						'id'          => 'wc_bis_form_header_text',
						'placeholder' => wc_bis_get_form_header_default_text(),
						'default'     => wc_bis_get_form_header_default_text(),
						'type'        => 'textarea',
					),

					array(
						'title'       => __( 'Sign-up form text &mdash; already signed up', 'woocommerce' ),
						'id'          => 'wc_bis_form_header_signed_up_text',
						'placeholder' => wc_bis_get_form_header_signed_up_default_text(),
						'default'     => wc_bis_get_form_header_signed_up_default_text(),
						'type'        => 'textarea',
						'desc'        => __( 'Text to display to logged-in customers who have already signed up, instead of the <strong>Sign-up form text</strong> above. <code>{manage_account_link}</code> will be substituted by the text below and converted into a <strong>My Account > Stock Notifications</strong> page link.', 'woocommerce' ),
					),

					array(
						'title'       => '',
						'id'          => 'wc_bis_form_header_signed_up_link_text',
						'placeholder' => wc_bis_get_form_header_signed_up_link_default_text(),
						'default'     => wc_bis_get_form_header_signed_up_link_default_text(),
						'type'        => 'text',
						'desc'        => __( 'Text substituted into <code>{manage_account_link}</code> above.', 'woocommerce' ),
					),

					array(
						'title'       => __( 'Sign-up form button text', 'woocommerce' ),
						'id'          => 'wc_bis_form_button_text',
						'placeholder' => wc_bis_get_form_button_default_text(),
						'default'     => wc_bis_get_form_button_default_text(),
						'type'        => 'text',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'bis_settings_general',
					),

					array(
						'title' => __( 'Catalog', 'woocommerce' ),
						'type'  => 'title',
						'id'    => 'bis_settings_catalog',
					),

					array(
						'title'   => __( 'Display sign-up prompt in catalog', 'woocommerce' ),
						'desc'    => __( 'Display a message next to out-of-stock products in catalog pages, prompting customers to sign up for stock notifications.', 'woocommerce' ),
						'id'      => 'wc_bis_loop_signup_prompt_status',
						'default' => 'no',
						'type'    => 'checkbox',
					),

					array(
						'title'       => __( 'Catalog sign-up prompt text', 'woocommerce' ),
						'id'          => 'wc_bis_loop_signup_prompt_text',
						'placeholder' => wc_bis_get_loop_signup_prompt_default_text(),
						'default'     => wc_bis_get_loop_signup_prompt_default_text(),
						'desc'        => __( 'Text to display next to out-of-stock products in catalog pages. <code>{prompt_link}</code> will be substituted by the text below and converted into a product page link.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'loop_signup_prompt_text',
					),

					array(
						'title'       => '',
						'id'          => 'wc_bis_loop_signup_prompt_link_text',
						'placeholder' => wc_bis_get_loop_signup_prompt_link_default_text(),
						'default'     => wc_bis_get_loop_signup_prompt_link_default_text(),
						'desc'        => __( 'Text substituted into <code>{prompt_link}</code> above.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'loop_signup_prompt_text',
					),

					array(
						'title'       => __( 'Catalog sign-up prompt text &mdash; already signed up', 'woocommerce' ),
						'id'          => 'wc_bis_loop_signup_prompt_signed_up_text',
						'placeholder' => wc_bis_get_loop_signup_prompt_signed_up_default_text(),
						'default'     => wc_bis_get_loop_signup_prompt_signed_up_default_text(),
						'desc'        => __( 'Text to display next to out-of-stock products in catalog pages to logged-in customers who have already signed up. <code>{prompt_link}</code> will be substituted by the text below and converted into a <strong>My Account > Stock Notifications</strong> page link.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'loop_signup_prompt_text',
					),

					array(
						'title'       => '',
						'id'          => 'wc_bis_loop_signup_prompt_signed_up_link_text',
						'placeholder' => wc_bis_get_loop_signup_prompt_signed_up_link_default_text(),
						'default'     => wc_bis_get_loop_signup_prompt_signed_up_link_default_text(),
						'desc'        => __( 'Text substituted into <code>{prompt_link}</code> above.', 'woocommerce' ),
						'type'        => 'text',
						'class'       => 'loop_signup_prompt_text',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'bis_settings_catalog',
					),
				);

				$bis_settings = apply_filters( 'woocommerce_bis_settings', $default_bis_settings );

				foreach ( $bis_settings as $setting ) {
					$settings[] = $setting;
				}
			}

			return $settings;
		}

		/**
		 * Add warning notice before displaying content.
		 */
		public function output() {

			if ( ! empty( $_GET['dismiss_wc_bis_onboarding'] ) ) {
				WC_BIS_Admin_Notices::remove_maintenance_notice( 'welcome' );
			}

			$force_output = false;

			// Hint: `woocommerce_registration_generate_password` make sure that this option is set to `yes`.
			if ( 'no' === get_option( 'woocommerce_registration_generate_password', 'no' ) && 'yes' === get_option( 'wc_bis_create_new_account_on_registration', 'no' ) ) {
				/* translators: %s settings page link */
				WC_BIS_Admin_Notices::add_notice( sprintf( __( 'WooCommerce is currently <a href="%s">configured</a> to create new accounts without generating passwords automatically. Guests who sign up to receive stock notifications will need to reset their password before they can log into their new account.', 'woocommerce' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ) ), 'warning' );

				// Force output.
				$force_output = true;
			}

			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				/* translators: %s settings page link */
				WC_BIS_Admin_Notices::add_notice( sprintf( __( 'WooCommerce is currently <a href="%s">configured</a> to hide out-of-stock products from your catalog. Customers will not be able sign up for back-in-stock notifications while this option is enabled.', 'woocommerce' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) ) ), 'warning' );

				// Force output.
				$force_output = true;
			}

			if ( $force_output ) {
				WC_BIS_Admin_Notices::output_notices();
			}

			parent::output();
		}
	}

endif;

return new WC_BIS_Settings();
