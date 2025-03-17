<?php
/**
 * WC_BIS_Notification_Data class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notification Data model class.
 *
 * @class    WC_BIS_Notification_Data
 * @version  x.x.x
 */
class WC_BIS_Notification_Data {

	/**
	 * Runtime cache of the product instance.
	 *
	 * @var null|WC_Product
	 */
	protected $product;

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'id'                 => 0,
		'type'               => 'one-time',
		'product_id'         => 0,
		'user_id'            => 0,
		'user_email'         => '',
		'create_date'        => 0,
		'last_notified_date' => 0,
		'subscribe_date'     => 0,
		'is_queued'          => 'off',
		'is_active'          => 'on',
		'is_verified'        => 'yes',
	);

	/**
	 * Stores meta data, defaults included.
	 * Meta keys are assumed unique by default. No meta is internal.
	 *
	 * @var array
	 */
	protected $meta_data = array();

	/**
	 * Sanitization function to apply to known meta values on the way in - @see sanitize_meta_value().
	 *
	 * @var array
	 */
	protected $meta_data_type_fn = array();

	/**
	 * Constructor.
	 *
	 * @param  int|object|array $notification ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $notification = 0 ) {
		if ( $notification instanceof WC_BIS_Notification_Data ) {
			$this->set_all( $notification->get_data() );
		} elseif ( is_array( $notification ) ) {
			$this->set_all( $notification );
		} else {
			$this->read( $notification );
		}
	}

	/**
	 * Change data to JSON format.
	 *
	 * @return string
	 */
	public function __toString() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * ------------------------------------------------------------
	 * Getters.
	 * ------------------------------------------------------------
	 */

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data() {
		return array_merge( $this->data, array( 'meta_data' => $this->get_meta_data() ) );
	}

	/**
	 * Get id.
	 *
	 * @return int
	 */
	public function get_id() {
		return absint( $this->data['id'] );
	}

	/**
	 * Get type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->data['type'];
	}

	/**
	 * Get product ID.
	 *
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->data['product_id'] );
	}

	/**
	 * Get product.
	 *
	 * @return WC_Product|false
	 */
	public function get_product() {
		if ( ! empty( $this->product ) ) {
			return $this->product;
		}

		if ( empty( $this->data['product_id'] ) ) {
			return false;
		}

		$product = wc_get_product( absint( $this->data['product_id'] ) );
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		$this->product = $product;
		return $product;
	}

	/**
	 * Get product link.
	 *
	 * @return string
	 */
	public function get_product_permalink() {

		$product = $this->get_product();
		if ( ! $product ) {
			return false;
		}

		if ( $product->is_type( 'variation' ) && ! empty( $this->get_meta( 'posted_attributes' ) ) ) {
			return $product->get_permalink( array( 'variation' => $this->get_meta( 'posted_attributes' ) ) );
		} else {
			return $product->get_permalink();
		}
	}

	/**
	 * Get product name.
	 *
	 * @return string
	 */
	public function get_product_name() {
		$product = $this->get_product();
		if ( ! $product ) {
			return false;
		}

		return $product->get_parent_id() ? $product->get_name() : $product->get_title();
	}


	/**
	 * Get product formatted variation list.
	 *
	 * @param  bool   $flat    Whether to flatten the list.
	 * @param  string $context The context.
	 * @return string
	 */
	public function get_product_formatted_variation_list( $flat = false, $context = 'view' ) {

		$product = $this->get_product();
		if ( ! $product ) {
			return false;
		}

		$formatted_variation_list = wc_get_formatted_variation( $product, $flat, true, true );

		if ( $product->is_type( 'variation' ) ) {

			// Replace list with custom data.
			$attributes = $this->get_meta( 'posted_attributes' );
			if ( $attributes ) {
				$attrs = array();
				foreach ( $attributes as $key => $value ) {

					if ( 0 === strpos( $key, 'attribute_pa_' ) ) {
						$attrs[ str_replace( 'attribute_', '', $key ) ] = $value;
					} else {
						// By pass converting global product attributes.
						$attrs[ wc_attribute_label( str_replace( 'attribute_', '', $key ), $product ) ] = $value;
					}
				}

				$formatted_variation_list = wc_get_formatted_variation( $attrs, $flat, true, true );
			}
		}

		if ( 'email' === $context ) {

			// Convert list to HTML table for better rendering.
			$formatted_variation_list = strtr(
				$formatted_variation_list,
				array(
					'<dl' => '<table',
					'<dd' => '<tr><th',
					'<dt' => '<tr><td',
					'dl>' => 'table>',
					'dd>' => 'th></tr>',
					'dt>' => 'td></tr>',
				)
			);
		}

		return $formatted_variation_list;
	}

	/**
	 * Get product formatted name.
	 *
	 * @return string
	 */
	public function get_product_formatted_name() {

		$product = $this->get_product();
		if ( ! $product ) {
			return false;
		}

		$name                     = $product->get_name();
		$formatted_variation_list = $this->get_product_formatted_variation_list( true );

		if ( $formatted_variation_list ) {
			/* translators: product name, identifier */
			$name = $product->get_name() . '<span class="description">' . $formatted_variation_list . '</span>';
		}

		return $name;
	}

	/**
	 * Get user ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->data['user_id'] );
	}

	/**
	 * Get user email.
	 *
	 * @return string
	 */
	public function get_user_email() {
		return $this->data['user_email'];
	}

	/**
	 * Get create date.
	 *
	 * @return int
	 */
	public function get_create_date() {
		return absint( $this->data['create_date'] );
	}

	/**
	 * Get subscribe again date.
	 *
	 * @return int
	 */
	public function get_subscribe_date() {
		return absint( $this->data['subscribe_date'] );
	}

	/**
	 * Get last notified date date.
	 *
	 * @return int
	 */
	public function get_last_notified_date() {
		return absint( $this->data['last_notified_date'] );
	}

	/**
	 * Get a generated sha256 hash unique for the notification.
	 *
	 * @return string
	 */
	public function get_hash() {

		$key = $this->get_meta( '_hash_key' );
		$iv  = $this->get_meta( '_hash_iv' );

		// Regenerate if needed.
		if ( empty( $key ) || empty( $iv ) ) {
			$this->setup_hash_data();

			/**
			 * We need to save the object here.
			 * This is needed for backwards compatibility lt 1.2.0.
			 *
			 * @see WC_BIS_Notification_Data::create()
			 */
			$this->save_meta_data();

			$key = $this->get_meta( '_hash_key' );
			$iv  = $this->get_meta( '_hash_iv' );
		}

		$input     = $this->get_id() . '-' . $this->get_product_id() . '-' . $this->get_create_date();
		$encrypted = openssl_encrypt( $input, 'AES-256-CBC', $key, 0, $iv );
		$hash      = hash( 'sha256', $encrypted );

		return $hash;
	}

	/**
	 * Get a verification hash.
	 *
	 * @since 1.2.0
	 *
	 * @param  string $code (Optional) If not specified, the code saved in metadata will be used.
	 * @return string  SHA-256 Hashed string
	 */
	public function get_verification_hash( $code = '' ) {
		if ( $this->maybe_setup_verification_data() ) {
			$this->save();
		}

		$code      = $code ? $code : $this->get_meta( '_verification_code' );
		$key       = $this->get_meta( '_verification_key' );
		$iv        = $this->get_meta( '_verification_iv' );
		$encrypted = openssl_encrypt( $code, 'AES-256-CBC', $key, 0, $iv );
		$hash      = hash( 'sha256', $encrypted );

		return $hash;
	}

	/**
	 * Get All Meta Data.
	 *
	 * @return array The meta data.
	 */
	public function get_meta_data() {
		return array_filter( $this->meta_data, array( $this, 'has_meta_value' ) );
	}

	/**
	 * Cleans null value meta when getting.
	 *
	 * @param  mixed $value The meta value.
	 * @return boolean
	 */
	private function has_meta_value( $value ) {
		return ! is_null( $value );
	}

	/**
	 * ------------------------------------------------------------
	 * Setters.
	 * ------------------------------------------------------------
	 */

	/**
	 * Set all data based on input array.
	 *
	 * @param  array $data The data.
	 * @return void
	 */
	public function set_all( $data ) {
		foreach ( $data as $key => $value ) {

			// Fix some strange namings.
			if ( 'is_active' === $key ) {
				$this->set_active( $value );
			} elseif ( 'is_queued' === $key ) {
				$this->set_queued_status( $value );
			} elseif ( 'is_verified' === $key ) {
				$this->set_verified_status( $value );
			} elseif ( is_callable( array( $this, "set_$key" ) ) ) {
				$this->{"set_$key"}( $value );
			} else {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * Set Notification ID.
	 *
	 * @param  int $value The notification ID.
	 * @return void
	 */
	public function set_id( $value ) {
		$this->data['id'] = absint( $value );
	}

	/**
	 * Set active status.
	 *
	 * @param  string $value The active status.
	 * @return void
	 */
	public function set_active( $value ) {
		$this->data['is_active'] = 'on' === $value ? 'on' : 'off';
	}

	/**
	 * Set active status.
	 *
	 * @param  string $value The queued status.
	 * @return void
	 */
	public function set_queued_status( $value ) {
		$this->data['is_queued'] = 'on' === $value ? 'on' : 'off';
	}

	/**
	 * Set locale.
	 *
	 * @since x.x.x
	 *
	 * @param  string $value Locale code.
	 * @return void
	 */
	public function set_locale( $value ) {
		$this->meta_data['_customer_locale'] = $value;
	}

	/**
	 * Set verified status.
	 *
	 * @since 1.2.0
	 *
	 * @param  string $value The verified status.
	 * @return void
	 */
	public function set_verified_status( $value ) {
		$this->data['is_verified'] = 'yes' === $value ? 'yes' : 'no';
	}

	/**
	 * Set type.
	 *
	 * @param  string $value The type.
	 * @return void
	 */
	public function set_type( $value ) {
		$this->data['type'] = $value;
	}

	/**
	 * Set product ID.
	 *
	 * @param  int $value The product ID.
	 * @return void
	 */
	public function set_product_id( $value ) {
		$this->data['product_id'] = absint( $value );
	}

	/**
	 * Set user ID.
	 *
	 * @param  string $value The user ID.
	 * @return void
	 */
	public function set_user_id( $value ) {
		$this->data['user_id'] = absint( $value );
	}

	/**
	 * Set user email.
	 *
	 * @param  string $value The user email.
	 * @return void
	 */
	public function set_user_email( $value ) {
		$this->data['user_email'] = $value;
	}

	/**
	 * Set create date.
	 *
	 * @param  string $value The create date.
	 * @return void
	 */
	public function set_create_date( $value ) {
		$this->data['create_date'] = absint( $value );
	}

	/**
	 * Set subscribe again date.
	 *
	 * @param  string $value The subscribe again date.
	 * @return void
	 */
	public function set_subscribe_date( $value ) {
		$this->data['subscribe_date'] = absint( $value );
	}

	/**
	 * Set last notified date.
	 *
	 * @param  string $value The last notified date.
	 * @return void
	 */
	public function set_last_notified_date( $value ) {
		$this->data['last_notified_date'] = absint( $value );
	}

	/**
	 * Set all meta data from array.
	 *
	 * @param  array $data The meta data.
	 */
	public function set_meta_data( $data ) {
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( $this->has_meta_value( $value ) ) {
					$this->meta_data[ $key ] = $this->sanitize_meta_value( $value, $key );
				}
			}
		}
	}

	/**
	 * Set Notification Product.
	 *
	 * @since x.x.x
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	public function set_product( WC_Product $product ): void {
		$this->product = $product;
		$this->set_product_id( $product->get_id() );
	}

	/**
	 * ------------------------------------------------------------
	 * CRUD.
	 * ------------------------------------------------------------
	 */

	/**
	 * Insert data into the database.
	 */
	private function create() {
		global $wpdb;

		$data = array(
			'type'               => $this->get_type(),
			'product_id'         => $this->get_product_id(),
			'user_id'            => $this->get_user_id(),
			'user_email'         => $this->get_user_email(),
			'create_date'        => $this->get_create_date(),
			'subscribe_date'     => $this->get_subscribe_date(),
			'last_notified_date' => $this->get_last_notified_date(),
			'is_queued'          => $this->is_queued() ? 'on' : 'off',
			'is_active'          => $this->is_active() ? 'on' : 'off',
			'is_verified'        => $this->is_verified() ? 'yes' : 'no',
		);

		$prepare_types = array( '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' );

		// Insert specific ID if included and bypass the Auto Increment column.
		if ( $this->get_id() ) {
			$data          = array_merge( array( 'id' => $this->get_id() ), $data );
			$prepare_types = array_merge( array( '%d' ), $prepare_types );
		}

		$inserted = $wpdb->insert( $wpdb->prefix . 'woocommerce_bis_notifications', $data, $prepare_types );

		if ( false !== $inserted ) {

			$this->set_id( $wpdb->insert_id );

			/**
			 * `woocommerce_bis_create_notification` filter.
			 *
			 * @since 1.2.0
			 */
			do_action( 'woocommerce_bis_create_notification', $this );

			return $wpdb->insert_id;
		}

		$this->set_id( $wpdb->insert_id );
	}

	/**
	 * Update data in the database.
	 */
	private function update() {
		global $wpdb;

		$data = array(
			'type'               => $this->get_type(),
			'product_id'         => $this->get_product_id(),
			'user_id'            => $this->get_user_id(),
			'user_email'         => $this->get_user_email(),
			'create_date'        => $this->get_create_date(),
			'subscribe_date'     => $this->get_subscribe_date(),
			'last_notified_date' => $this->get_last_notified_date(),
			'is_queued'          => $this->is_queued() ? 'on' : 'off',
			'is_active'          => $this->is_active() ? 'on' : 'off',
			'is_verified'        => $this->is_verified() ? 'yes' : 'no',
		);

		$updated = $wpdb->update( $wpdb->prefix . 'woocommerce_bis_notifications', $data, array( 'id' => $this->get_id() ), array( '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		/**
		 * Trigger action when a notification is updated.
		 *
		 * @since 9.9.0
		 *
		 * @param WC_BIS_Notification_Data $notification The notification data.
		 */
		do_action( 'woocommerce_bis_update_notification', $this );

		return $updated;
	}

	/**
	 * Delete data from the database.
	 */
	public function delete() {

		if ( $this->get_id() ) {
			global $wpdb;

			/**
			 * Trigger action before deleting a notification.
			 *
			 * @since 9.9.0
			 *
			 * @param WC_BIS_Notification_Data $notification The notification data.
			 */
			do_action( 'woocommerce_bis_before_delete_notification', $this );

			// Delete and clean up.
			$wpdb->delete( $wpdb->prefix . 'woocommerce_bis_notifications', array( 'id' => $this->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_bis_notificationsmeta', array( 'bis_notifications_id' => $this->get_id() ) );

			/**
			 * Trigger action when a notification is deleted.
			 *
			 * @since 9.9.0
			 *
			 * @param WC_BIS_Notification_Data $notification The notification data.
			 */
			do_action( 'woocommerce_bis_delete_notification', $this );
		}
	}

	/**
	 * Save data to the database.
	 *
	 * @return int
	 */
	public function save() {

		$this->validate();
		$this->update_customer_data();

		/**
		 * `woocommerce_bis_after_save_notification` filter.
		 *
		 * @since 1.2.0
		 */
		do_action( 'woocommerce_bis_before_save_notification', $this );

		if ( ! $this->get_id() ) {
			$saved = $this->create();
			$this->setup_hash_data();
		} else {
			$saved = $this->update();
		}

		$this->save_meta_data();

		/**
		 * `woocommerce_bis_after_save_notification` filter.
		 *
		 * @since 1.2.0
		 */
		do_action( 'woocommerce_bis_after_save_notification', $this );

		return false !== $saved ? $this->get_id() : false;
	}

	/**
	 * Read from DB object using ID.
	 *
	 * @param  int|WC_BIS_Notification_Data $notification The notification ID.
	 * @return void
	 */
	public function read( $notification ) {
		global $wpdb;

		if ( is_numeric( $notification ) && ! empty( $notification ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_bis_notifications WHERE id = %d LIMIT 1;", $notification ) );
		} elseif ( ! empty( $notification->id ) ) {
			$data = $notification;
		} else {
			$data = false;
		}

		if ( $data ) {
			$this->set_all( $data );
			$this->read_meta_data();
		}
	}

	/**
	 * Validates before saving for sanity.
	 */
	public function validate() {

		// Reset props for sanity.
		if ( ! empty( $this->get_user_email() ) && ! wc_bis_is_email( $this->get_user_email() ) ) {
			$this->set_user_email( '' );
		}

		// Sanity check the type.
		$types = wc_bis_get_notification_types();
		if ( ! in_array( $this->get_type(), array_keys( $types ), true ) ) {
			$default_type = array_pop( array_keys( $types ) );
			$this->set_type( $default_type );
		}

		// Reset active status for not verified notifications.
		if ( ! $this->is_verified() && $this->is_active() ) {
			$this->set_active( 'off' );
		}
	}

	/**
	 * ------------------------------------------------------------
	 * Utilities and actions.
	 * ------------------------------------------------------------
	 */

	/**
	 * Validates before saving for sanity.
	 *
	 * @since 1.3.2
	 */
	protected function update_customer_data() {

		// If there isn't a customer in this save action's context, then skip the update.
		if ( ! isset( WC()->customer ) || is_null( WC()->customer ) ) {
			return;
		}

		// Else, update the value, each time.
		$this->update_meta( '_customer_location_data', WC()->customer->get_taxable_address() );
	}

	/**
	 * Add an event.
	 *
	 * @param  string $type The type of event.
	 * @param  mixed  $user (Optional) User object or ID or email.
	 * @return bool
	 */
	public function add_event( $type, $user = false ) {

		// Manage event user.
		$user_id    = 0;
		$user_email = '';

		if ( ! empty( $user ) ) {

			if ( is_a( $user, 'WP_User' ) && ! empty( $user->ID ) && ! empty( $user->user_email ) ) {
				$user_id    = $user->ID;
				$user_email = $user->user_email;
			} elseif ( is_numeric( $user ) ) {

				$user = get_user_by( 'id', $user );
				if ( $user && is_a( $user, 'WP_User' ) ) {
					$user_id    = $user->ID;
					$user_email = $user->user_email;
				}
			} elseif ( wc_bis_is_email( $user ) ) {

				$user_email = $user;
				$user_obj   = get_user_by( 'email', $user );
				if ( $user_obj ) {
					$user_id    = $user_obj->ID;
					$user_email = $user_obj->user_email;
				}
			} else {
				$user_id    = 0;
				$user_email = $this->get_user_email();
			}
		}

		// Check user.
		if ( 0 !== $user_id && empty( $user_email ) ) {
			return false;
		}

		// Add event.
		try {

			$event = WC_BIS()->db->activity->add(
				array(
					'type'            => $type,
					'product_id'      => $this->get_product_id(),
					'notification_id' => $this->get_id(),
					'user_id'         => $user_id,
					'user_email'      => $user_email,
				)
			);

			if ( $event ) {
				return true;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Handle reactivation.
	 *
	 * @return void
	 */
	public function reactivate() {

		$this->set_active( 'on' );
		$this->add_event( 'reactivated', wp_get_current_user() );

		$product = $this->get_product();
		if ( $product && ! $product->is_in_stock() ) {
			$this->set_subscribe_date( time() );
		}

		/**
		 * Filter: `woocommerce_bis_notification_reactivate`.
		 *
		 * @since 9.9.0
		 *
		 * @param  WC_BIS_Notification_Data $notification_data The notification data.
		 */
		do_action( 'woocommerce_bis_notification_reactivate', $this );
	}

	/**
	 * Handle deactivation.
	 *
	 * @param  mixed $user (Optional) User object or ID or email.
	 * @return void
	 */
	public function deactivate( $user = false ) {
		if ( false === $user ) {
			$user = wp_get_current_user();
		}

		$this->set_active( 'off' );
		$this->set_queued_status( 'off' );
		$this->add_event( 'deactivated', $user );

		/**
		 * Filter: `woocommerce_bis_notification_deactivate`.
		 *
		 * @since 9.9.0
		 *
		 * @param  WC_BIS_Notification_Data $notification_data The notification data.
		 */
		do_action( 'woocommerce_bis_notification_deactivate', $this );
	}

	/**
	 * Setup hash data for handling notification specific secure requests (e.g. Unsubscribe).
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function setup_hash_data() {
		$key = hash( 'sha256', openssl_random_pseudo_bytes( 32 ) );
		$iv  = substr( hash( 'sha256', openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) ) ), 0, 16 );
		$this->update_meta( '_hash_key', $key );
		$this->update_meta( '_hash_iv', $iv );
	}

	/**
	 * Setup verification code and hash data used for double opt-in registration.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function setup_verification_data() {

		$code = sprintf( '%06d', wp_rand( 100000, 999999 ) );

		/**
		 * This filter handles the way plugin is generating verification codes.
		 *
		 * @since 1.2.0
		 *
		 * @param  string  $code
		 * @return string
		 */
		$code = apply_filters( 'woocommerce_bis_generate_verification_code', $code );
		$key  = hash( 'sha256', openssl_random_pseudo_bytes( 32 ) );
		$iv   = substr( hash( 'sha256', openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) ) ), 0, 16 );

		$this->update_meta( '_verification_code', $code );
		$this->update_meta( '_verification_created_at', time() );
		$this->update_meta( '_verification_key', $key );
		$this->update_meta( '_verification_iv', $iv );

		// Meta 'flag' to mark the notification for awaiting status.
		$this->update_meta( 'awaiting_verification', 'yes' );
	}

	/**
	 * Validates a notification's specific hash. (e.g. Unsubscribe).
	 *
	 * @since 1.2.0
	 *
	 * @param  string $hash_to_check The hash to check.
	 * @return bool
	 */
	public function validate_hash( $hash_to_check ) {
		return $hash_to_check === $this->get_hash();
	}

	/**
	 * Validates a given double opt-in verification code and hash.
	 *
	 * @since 1.2.0
	 *
	 * @param  string $code           Code string to check.
	 * @param  string $hash_to_check  Acts as a public key.
	 * @return bool
	 */
	public function validate_verification_code( $code, $hash_to_check ) {
		return $hash_to_check === $this->get_verification_hash( $code );
	}

	/**
	 * Generate verification code and data if needed.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function maybe_setup_verification_data() {
		if ( ! $this->is_verification_data_valid() ) {
			$this->setup_verification_data();
			return true;
		}

		return false;
	}

	/**
	 * Invalidates double opt-in verification code.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function invalidate_verification_data() {
		$this->update_meta( '_verification_code', '' );
		// Delete the 'awaiting' status flag.
		$this->delete_meta( 'awaiting_verification' );
	}

	/**
	 * ------------------------------------------------------------
	 * Conditionals.
	 * ------------------------------------------------------------
	 */

	/**
	 * Is queued.
	 *
	 * @return bool
	 */
	public function is_queued() {
		return 'on' === $this->data['is_queued'];
	}

	/**
	 * Is sent.
	 *
	 * @return bool
	 */
	public function is_delivered() {
		return ! $this->is_queued() && 0 !== $this->get_subscribe_date() && $this->get_last_notified_date() > $this->get_subscribe_date();
	}

	/**
	 * Is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'on' === $this->data['is_active'];
	}

	/**
	 * Is verified.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_verified() {
		return 'yes' === $this->data['is_verified'];
	}

	/**
	 * Is pending verification.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_pending() {
		return 'yes' === $this->get_meta( 'awaiting_verification' );
	}

	/**
	 * Validate verification code.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_verification_data_valid() {

		$code = $this->get_meta( '_verification_code' );
		$key  = $this->get_meta( '_verification_key' );
		$iv   = $this->get_meta( '_verification_iv' );

		if ( ! $this->is_pending() || $this->is_expired() || empty( $code ) || empty( $key ) || empty( $iv ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates verification expiration.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_expired() {
		$created_at     = (int) $this->get_meta( '_verification_created_at' );
		$time_threshold = wc_bis_get_verification_expiration_time_threshold();

		return $time_threshold && time() > $created_at + $time_threshold;
	}


	/**
	 * ------------------------------------------------------------
	 * Meta methods.
	 * ------------------------------------------------------------
	 */

	/**
	 * Get Meta by Key.
	 *
	 * @param  string $key The meta key.
	 * @return mixed
	 */
	public function get_meta( $key ) {

		$value = null;

		if ( isset( $this->meta_data[ $key ] ) ) {
			$value = $this->meta_data[ $key ];
		}

		return $value;
	}

	/**
	 * Add meta data.
	 *
	 * @param  string $key   The meta key.
	 * @param  string $value The meta value.
	 */
	public function add_meta( $key, $value ) {
		$this->update_meta( $key, $value );
	}

	/**
	 * Add meta data.
	 *
	 * @param  string $key   The meta key.
	 * @param  string $value The meta value.
	 */
	public function update_meta( $key, $value ) {
		if ( is_null( $value ) ) {
			$this->delete_meta( $key );
		} else {
			$this->meta_data[ $key ] = $this->sanitize_meta_value( $value, $key );
		}
	}

	/**
	 * Delete meta data.
	 *
	 * @param  array $key The meta key to delete.
	 */
	public function delete_meta( $key ) {
		$this->meta_data[ $key ] = null;
	}

	/**
	 * Read meta data from the database.
	 */
	protected function read_meta_data() {

		$this->meta_data = array();
		$cache_loaded    = false;

		if ( ! $this->get_id() ) {
			return;
		}

		$use_cache   = ! defined( 'WC_BIS_DEBUG_OBJECT_CACHE' ) && $this->get_id();
		$cache_key   = WC_Cache_Helper::get_cache_prefix( 'bis_notification_meta' ) . $this->get_id();
		$cached_meta = $use_cache ? wp_cache_get( $cache_key, 'bis_notification_meta' ) : false;

		if ( false !== $cached_meta ) {
			$this->meta_data = $cached_meta;
			$cache_loaded    = true;
		}

		if ( ! $cache_loaded ) {
			global $wpdb;
			$raw_meta_data = $wpdb->get_results(
				$wpdb->prepare(
					"
				SELECT meta_id, meta_key, meta_value
				FROM {$wpdb->prefix}woocommerce_bis_notificationsmeta
				WHERE bis_notifications_id = %d ORDER BY meta_id
			",
					$this->get_id()
				)
			);

			foreach ( $raw_meta_data as $meta ) {
				$this->meta_data[ $meta->meta_key ] = $this->sanitize_meta_value( $meta->meta_value, $meta->meta_key );
			}

			if ( $use_cache ) {
				wp_cache_set( $cache_key, $this->meta_data, 'bis_notification_meta' );
			}
		}
	}

	/**
	 * Update Meta Data in the database.
	 */
	protected function save_meta_data() {

		global $wpdb;
		$raw_meta_data = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT meta_id, meta_key, meta_value
			FROM {$wpdb->prefix}woocommerce_bis_notificationsmeta
			WHERE bis_notifications_id = %d ORDER BY meta_id
		",
				$this->get_id()
			)
		);

		$updated_meta_keys = array();

		// Update or delete meta from the db.
		if ( ! empty( $raw_meta_data ) ) {
			// Update or delete meta from the db depending on their presence.
			foreach ( $raw_meta_data as $meta ) {

				// Skip existing meta that hasn't changed.
				if ( isset( $this->meta_data[ $meta->meta_key ] ) && $this->sanitize_meta_value( $meta->meta_value, $meta->meta_key ) === $this->meta_data[ $meta->meta_key ] ) {
					$updated_meta_keys[] = (string) $meta->meta_key;
					continue;
				}

				if ( isset( $this->meta_data[ $meta->meta_key ] ) && null !== $this->meta_data[ $meta->meta_key ] && ! in_array( (string) $meta->meta_key, $updated_meta_keys, true ) ) {
					update_metadata_by_mid( 'bis_notifications', $meta->meta_id, $this->meta_data[ $meta->meta_key ], $meta->meta_key );
					$updated_meta_keys[] = (string) $meta->meta_key;
				} else {
					delete_metadata_by_mid( 'bis_notifications', $meta->meta_id );
				}
			}
		}

		// Add any meta that weren't updated.
		$add_meta_keys = array_diff( array_keys( $this->meta_data ), $updated_meta_keys );

		foreach ( $add_meta_keys as $meta_key ) {
			if ( null !== $this->meta_data[ $meta_key ] ) {
				add_metadata( 'bis_notifications', $this->get_id(), $meta_key, $this->meta_data[ $meta_key ], true );
			}
		}

		// Clear meta cache.
		$cache_key = WC_Cache_Helper::get_cache_prefix( 'bis_notification_meta' ) . $this->get_id();
		wp_cache_delete( $cache_key, 'bis_notification_meta' );

		$this->read_meta_data();
	}

	/**
	 * Meta value type sanitization on the way in.
	 *
	 * @param  mixed  $meta_value The meta value.
	 * @param  string $meta_key   The meta key.
	 * @return mixed
	 */
	private function sanitize_meta_value( $meta_value, $meta_key ) {

		// If the key is known, apply known sanitization function.
		if ( isset( $this->meta_data_type_fn[ $meta_key ] ) ) {

			$fn = $this->meta_data_type_fn[ $meta_key ];

			if ( 'on_or_off' === $fn ) {
				// 'off' by default.
				if ( is_bool( $meta_value ) ) {
					$meta_value = true === $meta_value ? 'on' : 'off';
				} else {
					$meta_value = 'on' === $meta_value ? 'on' : 'off';
				}
			} elseif ( 'absint_if_not_empty' === $fn ) {
				$meta_value = '' !== $meta_value ? absint( $meta_value ) : '';
			} elseif ( function_exists( $fn ) ) {
				$meta_value = $fn( $meta_value );
			}

			// Otherwise, always attempt to unserialize on the way in.
		} else {
			$meta_value = maybe_unserialize( $meta_value );
		}

		return $meta_value;
	}
}
