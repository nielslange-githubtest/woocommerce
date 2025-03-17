<?php
/**
 * WC_BIS_Activity_Data class
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
 * Activity Data model class.
 *
 * @class    WC_BIS_Activity_Data
 * @version  1.0.1
 */
class WC_BIS_Activity_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'id'              => 0,
		'type'            => '',
		'user_id'         => 0,
		'user_email'      => '',
		'product_id'      => 0,
		'notification_id' => 0,
		'object_id'       => 0,
		'amount'          => 0,
		'date'            => 0,
		'note'            => '',
	);

	/**
	 * Constructor.
	 *
	 * @param  int|object|array $activity ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $activity = 0 ) {
		if ( $activity instanceof WC_BIS_Activity_Data ) {
			$this->set_all( $activity->get_data() );
		} elseif ( is_array( $activity ) ) {
			$this->set_all( $activity );
		} else {
			$this->read( $activity );
		}
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
		return $this->data;
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
	 * Get product id.
	 *
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->data['product_id'] );
	}

	/**
	 * Get notification id.
	 *
	 * @return int
	 */
	public function get_notification_id() {
		return absint( $this->data['notification_id'] );
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
	 * Get user id.
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
	 * Get object id.
	 *
	 * @return int
	 */
	public function get_object_id() {
		return absint( $this->data['object_id'] );
	}

	/**
	 * Get date.
	 *
	 * @return int
	 */
	public function get_date() {
		return absint( $this->data['date'] );
	}

	/**
	 * Get note.
	 *
	 * @return string
	 */
	public function get_note() {
		return $this->data['note'];
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
			if ( is_callable( array( $this, "set_$key" ) ) ) {
				$this->{"set_$key"}( $value );
			} else {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * Set Activity ID.
	 *
	 * @param  int $value The activity ID.
	 * @return void
	 */
	public function set_id( $value ) {
		$this->data['id'] = absint( $value );
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
	 * Set notification ID.
	 *
	 * @param  int $value The notification ID.
	 * @return void
	 */
	public function set_notification_id( $value ) {
		$this->data['notification_id'] = absint( $value );
	}

	/**
	 * Set code.
	 *
	 * @param  string $value The type.
	 * @return void
	 */
	public function set_type( $value ) {
		$this->data['type'] = $value;
	}

	/**
	 * Set user ID.
	 *
	 * @param  int $value The user ID.
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
	 * Set object ID.
	 *
	 * @param  int $value The object ID.
	 * @return void
	 */
	public function set_object_id( $value ) {
		$this->data['object_id'] = absint( $value );
	}

	/**
	 * Set date.
	 *
	 * @param  int $value The date.
	 * @return void
	 */
	public function set_date( $value ) {
		$this->data['date'] = absint( $value );
	}

	/**
	 * Set note.
	 *
	 * @param  string $value The note.
	 * @return void
	 */
	public function set_note( $value ) {
		$this->data['note'] = $value;
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
			'type'            => $this->get_type(),
			'product_id'      => $this->get_product_id(),
			'notification_id' => $this->get_notification_id(),
			'user_id'         => $this->get_user_id(),
			'user_email'      => $this->get_user_email(),
			'object_id'       => $this->get_object_id(),
			'date'            => $this->get_date(),
			'note'            => $this->get_note(),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_bis_activity', $data, array( '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%s' ) );

		$this->set_id( $wpdb->insert_id );
	}

	/**
	 * Update data in the database.
	 */
	private function update() {
		global $wpdb;

		$data = array(
			'type'            => $this->get_type(),
			'product_id'      => $this->get_product_id(),
			'notification_id' => $this->get_notification_id(),
			'user_id'         => $this->get_user_id(),
			'user_email'      => $this->get_user_email(),
			'object_id'       => $this->get_object_id(),
			'date'            => $this->get_date(),
			'note'            => $this->get_note(),
		);

		$updated = $wpdb->update( $wpdb->prefix . 'woocommerce_bis_activity', $data, array( 'id' => $this->get_id() ), array( '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%s' ) );

		/**
		 * Action hook triggered after updating activity.
		 *
		 * @since 9.9.0
		 *
		 * @param  object $activity The activity.
		 */
		do_action( 'woocommerce_bis_update_activity', $this );

		return $updated;
	}

	/**
	 * Delete data from the database.
	 */
	public function delete() {

		if ( $this->get_id() ) {
			global $wpdb;

			/**
			 * Action hook triggered before deleting activity.
			 *
			 * @since 9.9.0
			 *
			 * @param  object $activity The activity.
			 */
			do_action( 'woocommerce_bis_before_delete_activity', $this );

			// Delete and clean up.
			$wpdb->delete( $wpdb->prefix . 'woocommerce_bis_activity', array( 'id' => $this->get_id() ) );

			/**
			 * Action hook triggered after deleting activity.
			 *
			 * @since 9.9.0
			 *
			 * @param  object $activity The activity.
			 */
			do_action( 'woocommerce_bis_delete_activity', $this );
		}
	}

	/**
	 * Save data to the database.
	 *
	 * @return int
	 */
	public function save() {

		$this->validate();

		if ( ! $this->get_id() ) {
			$this->create();
		} else {
			$this->update();
		}

		return $this->get_id();
	}

	/**
	 * Read from DB object using ID.
	 *
	 * @param  int $activity The activity.
	 * @return void
	 */
	public function read( $activity ) {
		global $wpdb;

		if ( is_numeric( $activity ) && ! empty( $activity ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_bis_activity WHERE id = %d LIMIT 1;", $activity ) );
		} elseif ( ! empty( $activity->id ) ) {
			$data = $activity;
		} else {
			$data = false;
		}

		if ( $data ) {
			$this->set_all( $data );
		}
	}

	/**
	 * Validates before saving for sanity.
	 */
	public function validate() {
		// ...
	}
}
