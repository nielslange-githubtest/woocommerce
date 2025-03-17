<?php
/**
 * WC_BIS_Notifications_DB class
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
 * DB API class.
 *
 * @version  9.9.0
 */
class WC_BIS_Notifications_DB {

	/**
	 * Cloning is forbidden.
	 *
	 * @since 9.9.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 9.9.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Query notifications data from the DB.
	 *
	 * @param  array $args The query arguments.
	 * @return array|int
	 */
	public function query( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'type'                => '',
				'product_id'          => 0,
				'user_id'             => false,
				'user_email'          => false,
				'create_date'         => 0,
				'subscribe_date'      => 0,
				'is_queued'           => '',
				'is_active'           => '',
				'is_verified'         => '',
				'start_date'          => 0,
				'end_date'            => 0,
				'start_notified_date' => 0,
				'end_notified_date'   => 0,
				'last_sent_throttle'  => 0,
				'is_subscribed'       => false,
				'product_exists'      => '',
				'product_status'      => '',
				'search'              => '',
				'count'               => false,
				'order_by'            => array( 'id' => 'ASC' ),
				'limit'               => -1,
				'offset'              => -1,
				'return'              => 'all', // Other options: 'ids' | 'objects'.
				'meta_query'          => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		$table = $wpdb->prefix . 'woocommerce_bis_notifications';

		if ( $args['count'] ) {

			$select = "COUNT( {$table}.id )";

		} elseif ( in_array( (string) $args['return'], array( 'ids' ), true ) ) {

				$select = $table . '.id';
		} else {
			$select = '*';
		}

		// Define JOIN statements.
		$products_join   = " INNER JOIN {$wpdb->posts} AS _products ON {$table}.product_id = _products.ID";
		$variations_join = " LEFT JOIN {$wpdb->posts} AS _product_parents ON _products.post_parent = _product_parents.ID";

		// Build the query.
		$sql      = 'SELECT ' . $select . " FROM {$table}";
		$join     = '';
		$where    = '';
		$order_by = '';

		$where_clauses    = array( '1=1' );
		$where_values     = array();
		$order_by_clauses = array();

		// WHERE clauses.

		if ( $args['type'] ) {

			$types = is_array( $args['type'] ) ? $args['type'] : array( $args['type'] );
			$types = array_map( 'esc_sql', $types );

			$where_clauses[] = "{$table}.type IN ( " . implode( ', ', array_fill( 0, count( $types ), '%s' ) ) . ' )';
			$where_values    = array_merge( $where_values, $types );
		}

		if ( $args['product_id'] ) {
			$product_ids = array_map( 'absint', is_array( $args['product_id'] ) ? $args['product_id'] : array( $args['product_id'] ) );
			$product_ids = array_map( 'esc_sql', $product_ids );

			$where_clauses[] = "{$table}.product_id IN ( " . implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) ) . ' )';
			$where_values    = array_merge( $where_values, $product_ids );
		}

		if ( $args['user_id'] || 0 === $args['user_id'] ) {
			$user_ids = array_map( 'absint', is_array( $args['user_id'] ) ? $args['user_id'] : array( $args['user_id'] ) );
			$user_ids = array_map( 'esc_sql', $user_ids );

			$where_clauses[] = "{$table}.user_id IN ( " . implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) ) . ' )';
			$where_values    = array_merge( $where_values, $user_ids );
		}

		if ( $args['user_email'] || '' === $args['user_email'] ) {

			$user_emails = is_array( $args['user_email'] ) ? $args['user_email'] : array( $args['user_email'] );
			$user_emails = array_map( 'esc_sql', $user_emails );

			$where_clauses[] = "{$table}.user_email IN ( " . implode( ', ', array_fill( 0, count( $user_emails ), '%s' ) ) . ' )';
			$where_values    = array_merge( $where_values, $user_emails );
		}

		if ( $args['search'] ) {
			$s               = esc_sql( '%' . $args['search'] . '%' );
			$where_clauses[] = "{$table}.user_email LIKE %s";
			$where_values    = array_merge( $where_values, array_fill( 0, 1, $s ) );
		}

		if ( $args['is_active'] ) {
			$is_active       = 'on' === $args['is_active'] ? 'on' : 'off';
			$where_clauses[] = "{$table}.is_active = %s";
			$where_values    = array_merge( $where_values, array( $is_active ) );
		}

		if ( $args['is_verified'] ) {
			$is_verified     = 'yes' === $args['is_verified'] ? 'yes' : 'no';
			$where_clauses[] = "{$table}.is_verified = %s";
			$where_values    = array_merge( $where_values, array( $is_verified ) );
		}

		if ( $args['is_queued'] ) {
			$is_queued       = 'on' === $args['is_queued'] ? 'on' : 'off';
			$where_clauses[] = "{$table}.is_queued = %s";
			$where_values    = array_merge( $where_values, array( $is_queued ) );
		}

		if ( $args['start_date'] ) {
			$start_date      = absint( $args['start_date'] );
			$where_clauses[] = "{$table}.create_date >= %d";
			$where_values    = array_merge( $where_values, array( $start_date ) );
		}

		if ( $args['end_date'] ) {
			$end_date        = absint( $args['end_date'] );
			$where_clauses[] = "{$table}.create_date < %d";
			$where_values    = array_merge( $where_values, array( $end_date ) );
		}

		if ( $args['create_date'] ) {
			$create_date     = absint( $args['create_date'] );
			$where_clauses[] = "{$table}.create_date >= %d";
			$where_values    = array_merge( $where_values, array( $create_date ) );
		}

		if ( $args['start_notified_date'] ) {
			$start_date      = absint( $args['start_notified_date'] );
			$where_clauses[] = "{$table}.last_notified_date >= %d";
			$where_values    = array_merge( $where_values, array( $start_date ) );
		}

		if ( $args['end_notified_date'] ) {
			$end_date        = absint( $args['end_notified_date'] );
			$where_clauses[] = "{$table}.last_notified_date < %d";
			$where_values    = array_merge( $where_values, array( $end_date ) );
		}

		if ( $args['last_sent_throttle'] ) {
			$throttle        = absint( $args['last_sent_throttle'] );
			$where_clauses[] = "{$table}.last_notified_date <= %d";
			$where_values    = array_merge( $where_values, array( time() - $throttle ) );
		}

		if ( true === $args['is_subscribed'] ) {
			$where_clauses[] = "{$table}.subscribe_date > {$table}.last_notified_date";
		}

		if ( '' !== $args['product_exists'] ) {

			$exists = (bool) $args['product_exists'];
			switch ( $exists ) {
				case true:
					$where_clauses[] = '_products.post_status <> %s';
					break;
				case false:
					$where_clauses[] = '_products.post_status = %s';
					break;
			}

			$join        .= $products_join;
			$where_values = array_merge( $where_values, array( 'trash' ) );
		}

		if ( '' !== $args['product_status'] ) {

			$valid_statuses = array_keys( get_post_statuses() );
			$post_status    = esc_sql( $args['product_status'] );
			if ( in_array( $post_status, $valid_statuses, true ) ) {

				// Maybe add JOIN statement.
				if ( false === strpos( $join, '_products' ) ) {
					$join .= $products_join;
				}

				if ( false === strpos( $join, '_product_parents' ) ) {
					$join .= $variations_join;
				}

				$where_clauses[] = '_products.post_status = %s';
				$where_clauses[] = '( ( _products.post_parent <> 0 AND _product_parents.post_status = %s ) OR ( _products.post_parent = 0 ) )';
				$where_values    = array_merge( $where_values, array( $post_status, $post_status ) );
			}
		}

		// ORDER BY clauses.
		if ( $args['order_by'] && is_array( $args['order_by'] ) ) {
			foreach ( $args['order_by'] as $what => $how ) {
				$order_by_clauses[] = $table . '.' . esc_sql( strval( $what ) ) . ' ' . esc_sql( strval( $how ) );
			}
		}

		$order_by_clauses = empty( $order_by_clauses ) ? array( $table . '.id, ASC' ) : $order_by_clauses;

		// Build SQL query components.

		$where    = ' WHERE ' . implode( ' AND ', $where_clauses );
		$order_by = ' ORDER BY ' . implode( ', ', $order_by_clauses );
		$limit    = $args['limit'] > 0 ? ' LIMIT ' . absint( $args['limit'] ) : '';
		$offset   = $args['offset'] > 0 ? ' OFFSET ' . absint( $args['offset'] ) : '';

		// Append meta query SQL components.
		if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {

			$meta_query = new WP_Meta_Query();
			$meta_query->parse_query_vars( $args );
			$meta_sql = $meta_query->get_sql( 'bis_notifications', $table, 'id' );
			if ( ! empty( $meta_sql ) ) {
				// Meta query JOIN clauses.
				if ( ! empty( $meta_sql['join'] ) ) {
					$join .= $meta_sql['join'];
				}
				// Meta query WHERE clauses.
				if ( ! empty( $meta_sql['where'] ) ) {
					$where .= $meta_sql['where'];
				}
			}
		}

		// Assemble and run the query.
		$sql .= $join . $where . $order_by . $limit . $offset;

		/**
		 * WordPress.DB.PreparedSQL.NotPrepared explained.
		 *
		 * The sniff isn't smart enough to follow $sql variable back to its source. So it doesn't know whether the query in $sql incorporates user-supplied values or not.
		 *
		 * @see https://github.com/WordPress/WordPress-Coding-Standards/issues/469
		 */

		// Allocate ref.
		$db = $wpdb;
		if ( $args['count'] ) {

			if ( empty( $where_values ) ) {
				$count = absint( $db->get_var( $sql ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$count = absint( $db->get_var( $db->prepare( $sql, $where_values ) ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			// Deallocate ref.
			unset( $db );

			return $count;
		} else {

			if ( empty( $where_values ) ) {
				$results = $db->get_results( $sql ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$results = $db->get_results( $db->prepare( $sql, $where_values ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			// Deallocate ref.
			unset( $db );
		}

		if ( empty( $results ) ) {
			return array();
		}

		$a = array();

		if ( 'objects' === $args['return'] ) {
			foreach ( $results as $result ) {
				$a[] = self::get( $result->id );
			}
		} elseif ( 'ids' === $args['return'] ) {
			foreach ( $results as $result ) {
				$a[] = (int) $result->id;
			}
		} else {
			foreach ( $results as $result ) {
				$a[] = (array) $result;
			}
		}

		return $a;
	}

	/**
	 * Get a record from the DB.
	 *
	 * @since 9.9.0
	 * @param  mixed $notification The notification ID or object.
	 * @return false|WC_BIS_Notification_Data
	 */
	public function get( $notification ) {

		if ( is_numeric( $notification ) ) {
			$notification = absint( $notification );
			$notification = new WC_BIS_Notification_Data( $notification );
		} elseif ( $notification instanceof WC_BIS_Notification_Data ) {
			$notification = new WC_BIS_Notification_Data( $notification );
		} elseif ( is_object( $notification ) ) {
			$notification = new WC_BIS_Notification_Data( (array) $notification );
		} else {
			$notification = false;
		}

		if ( ! $notification || ! is_object( $notification ) || ! $notification->get_id() ) {
			return false;
		}

		return $notification;
	}

	/**
	 * Get a notification from the DB using hash.
	 *
	 * @since 9.9.0
	 * @param  string $hash The notification hash.
	 * @return false|WC_BIS_Notification_Data
	 */
	public function get_by_hash( $hash ) {

		// Hint: This will be deprecated after dropping support for lt 1.2.0. @see WC_BIS_Account::process_unsubscribe().

		$hash  = urldecode( $hash );
		$input = wc_bis_notification_hash( $hash, 'decrypt' );
		$ids   = explode( '-', $input );

		if ( empty( $ids ) || count( $ids ) !== 3 ) {
			return false;
		}

		$notification = $this->get( absint( $ids[0] ) );
		if ( $notification && $notification->get_id() && absint( $ids[1] ) === $notification->get_product_id() && absint( $ids[2] ) === $notification->get_create_date() ) {
			return $notification;
		}

		return false;
	}

	/**
	 * Create a record in the DB.
	 *
	 * @since 9.9.0
	 * @param  array $args The notification data.
	 * @return false|int
	 * @throws Exception When validation fails.
	 */
	public function add( $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'type'               => 'one-time',
				'product_id'         => 0,
				'user_id'            => 0,
				'user_email'         => '',
				'create_date'        => 0,
				'last_notified_date' => 0,
				'subscribe_date'     => 0,
				'locale'             => '',
				'is_queued'          => 'off',
				'is_active'          => 'on',
				'is_verified'        => 'yes',
			)
		);

		$this->validate( $args );

		$notification = new WC_BIS_Notification_Data(
			array(
				'type'               => $args['type'],
				'product_id'         => $args['product_id'],
				'user_id'            => $args['user_id'],
				'user_email'         => $args['user_email'],
				'create_date'        => $args['create_date'],
				'last_notified_date' => $args['last_notified_date'],
				'subscribe_date'     => $args['subscribe_date'],
				'locale'             => $args['locale'],
				'is_queued'          => 'on' === $args['is_queued'] ? 'on' : 'off',
				'is_active'          => 'on' === $args['is_active'] ? 'on' : 'off',
				'is_verified'        => 'yes' === $args['is_verified'] ? 'yes' : 'no',
			)
		);

		return $notification->save();
	}

	/**
	 * Update a record in the DB.
	 *
	 * @param  WC_BIS_Notification_Data|int $notification The Notification ID or object.
	 * @param  array                        $args         The data to update.
	 * @return bool
	 * @throws Exception When validation fails.
	 */
	public function update( $notification, $args ) {

		if ( is_numeric( $notification ) ) {
			$notification = absint( $notification );
			$notification = new WC_BIS_Notification_Data( $notification );
		}

		if ( is_object( $notification ) && $notification->get_id() && ! empty( $args ) && is_array( $args ) ) {

			$this->validate( $args, $notification );

			$notification->set_all( $args );

			return $notification->save();
		}

		return false;
	}

	/**
	 * Validate data.
	 *
	 * @since 9.9.0
	 * @param  array                    &$args         The data to validate.
	 * @param  WC_BIS_Notification_Data $notification  The notification object.
	 * @return void
	 * @throws Exception When validation fails.
	 */
	public function validate( &$args, $notification = false ) {
		if ( ! empty( $args['user_email'] ) && ! filter_var( $args['user_email'], FILTER_VALIDATE_EMAIL ) ) {
			/* translators: %s: Email input string */
			throw new Exception( sprintf( esc_html__( 'Invalid e-mail: %s.', 'woocommerce' ), esc_html( $args['user_email'] ) ) );
		}

		// New Sub.
		if ( ! $notification || ! $notification->get_id() ) {
			if ( empty( $args['product_id'] ) ) {
				throw new Exception( esc_html__( 'Product is empty.', 'woocommerce' ) );
			}

			if ( empty( $args['user_id'] ) && empty( $args['user_email'] ) ) {
				throw new Exception( esc_html__( 'Customer is empty.', 'woocommerce' ) );
			}

			// Pre-fill date, if not already filled.
			if ( empty( $args['create_date'] ) ) {
				$args['create_date'] = time();
			}
		}
	}

	/**
	 * Delete a record from the DB.
	 *
	 * @since 9.9.0
	 * @param  mixed $notification The notification ID or object.
	 * @return void
	 */
	public function delete( $notification ) {
		$notification = self::get( $notification );
		if ( $notification ) {
			$notification->delete();
		}
	}

	/**
	 * Get distinct dates.
	 *
	 * @return array
	 */
	public function get_distinct_dates() {
		global $wpdb;

		$months = $wpdb->get_results(
			"
			SELECT DISTINCT YEAR( FROM_UNIXTIME( {$wpdb->prefix}woocommerce_bis_notifications.`create_date` ) ) AS year, MONTH( FROM_UNIXTIME( {$wpdb->prefix}woocommerce_bis_notifications.`create_date` ) ) AS month
			FROM {$wpdb->prefix}woocommerce_bis_notifications
			ORDER BY {$wpdb->prefix}woocommerce_bis_notifications.`create_date` DESC
			"
		);

		return $months;
	}

	/**
	 * Get most delayed products.
	 *
	 * @since 9.9.0
	 * @param  int $limit The number of products to return.
	 * @return array
	 */
	public function get_delayed_products( $limit = 5 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT product_id, max( {$wpdb->prefix}woocommerce_bis_notifications.`subscribe_date` ) as `waiting_since`
				FROM {$wpdb->prefix}woocommerce_bis_notifications
				INNER JOIN {$wpdb->posts} ON `{$wpdb->posts}`.`ID` = {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				WHERE {$wpdb->prefix}woocommerce_bis_notifications.`subscribe_date` > {$wpdb->prefix}woocommerce_bis_notifications.`last_notified_date`
				AND {$wpdb->prefix}woocommerce_bis_notifications.`is_active` = 'on'
				GROUP BY {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				ORDER BY `waiting_since` ASC
				LIMIT %d;
				",
				$limit
			)
		);

		return $results;
	}

	/**
	 * Get most anticipated products.
	 *
	 * @since 9.9.0
	 * @param  int $limit The number of products to return.
	 * @return array
	 */
	public function get_anticipated_products( $limit = 5 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT product_id, count( product_id ) as `count`
				FROM {$wpdb->prefix}woocommerce_bis_notifications
				INNER JOIN {$wpdb->posts} ON `{$wpdb->posts}`.`ID` = {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				WHERE {$wpdb->prefix}woocommerce_bis_notifications.`subscribe_date` > {$wpdb->prefix}woocommerce_bis_notifications.`last_notified_date`
				AND {$wpdb->prefix}woocommerce_bis_notifications.`is_active` = 'on'
				GROUP BY {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				ORDER BY `count` DESC
				LIMIT %d;
				",
				$limit
			)
		);

		return $results;
	}

	/**
	 * Get most subscribed products.
	 *
	 * @since 9.9.0
	 * @param  int $start The start timestamp.
	 * @param  int $limit The number of products to return.
	 * @param  int $end   The end timestamp.
	 * @return array
	 */
	public function get_most_subscribed_products( $start = 0, $limit = 5, $end = 0 ) {
		global $wpdb;

		if ( ! $start ) {
			$start = strtotime( '-1 week' );
		}

		if ( ! $end ) {
			$end = time();
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT product_id, count( product_id ) as `total`
				FROM {$wpdb->prefix}woocommerce_bis_notifications
				INNER JOIN {$wpdb->posts} ON `{$wpdb->posts}`.`ID` = {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				WHERE {$wpdb->prefix}woocommerce_bis_notifications.`create_date` BETWEEN %d AND %d
				GROUP BY {$wpdb->prefix}woocommerce_bis_notifications.`product_id`
				ORDER BY `total` DESC
				LIMIT %d;
				",
				$start,
				$end,
				$limit
			)
		);

		return $results;
	}

	/**
	 * Bulk enqueue notifications.
	 *
	 * @param  array|int $product_ids The product IDs.
	 * @param  bool      $force       Whether to force the queue.
	 * @return int
	 */
	public function bulk_enqueue_by_product_id( $product_ids, $force = false ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		global $wpdb;

		$product_ids          = array_map( 'absint', $product_ids );
		$product_placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$threshold            = wc_bis_get_minimum_time_between_notifications();
		$throttle             = time() - $threshold;

		$sql            = "
			UPDATE `{$wpdb->prefix}woocommerce_bis_notifications`
			SET `is_queued` = 'on'
			WHERE `product_id` IN ({$product_placeholders})
			AND `is_active` = 'on'
			AND `is_queued` = 'off'
			AND `subscribe_date` > `last_notified_date`
			";
		$prepare_values = array( ...$product_ids );

		if ( $threshold && ! $force ) {
			$sql             .= ' AND `last_notified_date` <= %d';
			$prepare_values[] = $throttle;
		}

		$wp     = $wpdb;
		$update = $wp->query(
			$wp->prepare(
				$sql,
				$prepare_values
			)
		);
		unset( $wp );

		return $update;
	}

	/**
	 * Bulk set queue status.
	 *
	 * @since 9.9.0
	 * @deprecated 9.9.0
	 * @param  array  $notification_ids The notification IDs.
	 * @param  string $value            The queue status value.
	 * @return int
	 */
	public function bulk_set_queue_status( $notification_ids, $value = 'on' ) {
		_deprecated_function( __METHOD__, '9.9.0', 'WC_BIS_Notifications_DB::bulk_enqueue_by_product_id()' );
		if ( ! is_array( $notification_ids ) ) {
			$notification_ids = array( $notification_ids );
		}

		$notification_ids          = array_map( 'absint', $notification_ids );
		$notification_placeholders = implode( ',', array_fill( 0, count( $notification_ids ), '%d' ) );
		$prepare_values            = array_merge( array( 'on' === $value ? 'on' : 'off' ), $notification_ids );

		global $wpdb;
		$wp     = $wpdb;
		$update = $wp->query(
			$wp->prepare(
				"
				UPDATE {$wpdb->prefix}woocommerce_bis_notifications
				SET `is_queued` = %s
				WHERE `id` IN ({$notification_placeholders})
				",
				$prepare_values
			)
		);
		unset( $wp );

		return $update;
	}

	/**
	 * Bulk renew subscribe dates.
	 *
	 * @since 9.9.0
	 * @deprecated 9.9.0
	 * @param  array|int $notification_ids The notification IDs.
	 * @return int
	 */
	public function bulk_renew_subscribe_dates( $notification_ids ) {
		_deprecated_function( __METHOD__, '9.9.0', 'WC_BIS_Notifications_DB::bulk_enqueue_by_product_id()' );
		if ( ! is_array( $notification_ids ) ) {
			$notification_ids = array( $notification_ids );
		}

		$notification_ids          = array_map( 'absint', $notification_ids );
		$notification_placeholders = implode( ',', array_fill( 0, count( $notification_ids ), '%d' ) );
		$prepare_values            = array_merge( array( time() ), $notification_ids );

		global $wpdb;
		$wp     = $wpdb;
		$update = $wp->query(
			$wp->prepare(
				"
				UPDATE {$wpdb->prefix}woocommerce_bis_notifications
				SET `subscribe_date` = %d
				WHERE `id` IN ({$notification_placeholders})
				",
				$prepare_values
			)
		);
		unset( $wp );

		return $update;
	}

	/**
	 * Get notifications number per day.
	 *
	 * @since 9.9.0
	 * @param  int    $date_start   Start date as a Unix timestamp.
	 * @param  int    $date_end     End date as a Unix timestamp.
	 * @param  string $date_column  Date column name.
	 * @return array
	 */
	public function get_notifications_number_per_day( int $date_start, int $date_end, string $date_column ): array {
		global $wpdb;

		$notifications = (array) $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT
				COUNT(*) as `count`,
				DATE_FORMAT(FROM_UNIXTIME(%i), '%%Y-%%m-%%d') as `date`
				FROM `{$wpdb->prefix}woocommerce_bis_notifications`
				WHERE %i >= %d
				AND %i <= %d
				GROUP BY `date`
			", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				array(
					$date_column,
					$date_column,
					$date_start,
					$date_column,
					$date_end,
				)
			),
			ARRAY_A
		);

		return $notifications;
	}

	/**
	 * Bulk renew subscribe dates by product id.
	 *
	 * @since 9.9.0
	 * @param  array|int $product_ids The product IDs.
	 * @return int
	 */
	public function bulk_renew_subscribe_dates_by_product_id( $product_ids ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$product_ids = array_map( 'absint', $product_ids );
		global $wpdb;
		$wp                      = $wpdb;
		$product_ids_placeholder = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

		$update = $wp->query(
			$wp->prepare(
				"
				UPDATE `{$wpdb->prefix}woocommerce_bis_notifications`
				SET `subscribe_date` = %d
				WHERE `product_id` IN ($product_ids_placeholder)
				AND (`last_notified_date` > `subscribe_date` OR `subscribe_date` = 0)
				",
				time(),
				...$product_ids
			)
		);
		unset( $wp );

		return $update;
	}

	/**
	 * Get subscribed notifications count per product.
	 *
	 * @since 9.9.0
	 * @param  array|int $product_ids   The product IDs.
	 * @param  int       $sent_throttle The throttle time in seconds.
	 * @return array {
	 *    @type int $allowed   The allowed notifications.
	 *    @type int $throttled The throttled notifications.
	 * }
	 */
	public function get_subscribed_notifications_count_per_product( $product_ids, $sent_throttle = 0 ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$product_ids = array_map( 'absint', $product_ids );
		global $wpdb;
		$wp                      = $wpdb;
		$product_ids_placeholder = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

		if ( ! $sent_throttle ) {

			$count = $wp->get_results(
				$wp->prepare(
					"
					SELECT
						COUNT(1) as `count`
					FROM
						`{$wpdb->prefix}woocommerce_bis_notifications`
					WHERE
						product_id IN ($product_ids_placeholder)
						AND is_active = 'on'
						AND is_queued = 'off'
						AND subscribe_date > last_notified_date
					",
					...$product_ids
				),
				ARRAY_A
			);

			// Format results.
			$allowed   = $count[0]['count'] ?? 0;
			$throttled = 0;

		} else {

			$results = $wp->get_results(
				$wp->prepare(
					"
					SELECT
						SUM(
							CASE
								WHEN bn.last_notified_date <= % d THEN 1
								ELSE 0
							END
						) AS allowed,
						SUM(
							CASE
								WHEN NOT bn.last_notified_date <= % d THEN 1
								ELSE 0
							END
						) AS throttled
					FROM
						`{$wpdb->prefix}woocommerce_bis_notifications` AS bn
					WHERE
						bn.product_id IN ($product_ids_placeholder)
						AND bn.is_active = 'on'
						AND bn.is_queued = 'off'
						AND subscribe_date > last_notified_date
					",
					$sent_throttle,
					$sent_throttle,
					...$product_ids
				),
				ARRAY_A
			);

			// Format results.
			$allowed   = $results[0]['allowed'] ?? 0;
			$throttled = $results[0]['throttled'] ?? 0;
		}

		unset( $wp );

		return array(
			'allowed'   => (int) $allowed,
			'throttled' => (int) $throttled,
		);
	}
}
