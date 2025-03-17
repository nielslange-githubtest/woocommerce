<?php
/**
 * WC_BIS_Activity_DB class
 *
 * @package  WooCommerce Νotifications
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
 * @version  1.1.0
 */
class WC_BIS_Activity_DB {

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Query activiry data from the DB.
	 *
	 * @since 9.9.0
	 *
	 * Example arguments array:
	 * {
	 *     @type  string     $return           Return array format:
	 *
	 *         - 'all': entire row casted to array,
	 *         - 'ids': ids only,
	 *         - 'objects': WC_BIS_Activity_Data objects.
	 * }
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return array|int
	 */
	public function query( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'return'                    => 'all', // Other options: 'ids' | 'objects'.
				'count'                     => false,
				'search'                    => '',
				'type'                      => '',
				'product_id'                => 0,
				'notification_id'           => 0,
				'only_active_notifications' => null,
				'user_id'                   => false,
				'user_email'                => false,
				'object_id'                 => 0,
				'date'                      => 0,
				'note'                      => '',
				'start_date'                => '',
				'end_date'                  => '',
				'order_by'                  => array( 'id' => 'ASC' ),
				'limit'                     => -1,
				'offset'                    => -1,
			)
		);

		$table = $wpdb->prefix . 'woocommerce_bis_activity';

		if ( $args['count'] ) {

			$select = "COUNT( {$table}.id )";

		} elseif ( in_array( (string) $args['return'], array( 'ids' ), true ) ) {

				$select = $table . '.id';
		} else {
			$select = '*';
		}

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

		if ( $args['product_id'] ) {
			$product_ids = array_map( 'absint', is_array( $args['product_id'] ) ? $args['product_id'] : array( $args['product_id'] ) );
			$product_ids = array_map( 'esc_sql', $product_ids );

			$where_clauses[] = "{$table}.product_id IN ( " . implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) ) . ' )';
			$where_values    = array_merge( $where_values, $product_ids );
		}

		if ( $args['notification_id'] ) {
			$notification_ids = array_map( 'absint', is_array( $args['notification_id'] ) ? $args['notification_id'] : array( $args['notification_id'] ) );
			$notification_ids = array_map( 'esc_sql', $notification_ids );

			$where_clauses[] = "{$table}.notification_id IN ( " . implode( ', ', array_fill( 0, count( $notification_ids ), '%d' ) ) . ' )';
			$where_values    = array_merge( $where_values, $notification_ids );
		}

		if ( $args['object_id'] ) {
			$object_ids = array_map( 'absint', is_array( $args['object_id'] ) ? $args['object_id'] : array( $args['object_id'] ) );
			$object_ids = array_map( 'esc_sql', $object_ids );

			$where_clauses[] = "{$table}.object_id IN ( " . implode( ', ', array_fill( 0, count( $object_ids ), '%d' ) ) . ' )';
			$where_values    = array_merge( $where_values, $object_ids );
		}

		if ( $args['search'] ) {
			$s               = esc_sql( '%' . $args['search'] . '%' );
			$where_clauses[] = "{$table}.user_email LIKE %s";
			$where_values    = array_merge( $where_values, array_fill( 0, 1, $s ) );
		}

		if ( $args['start_date'] ) {
			$start_date      = absint( $args['start_date'] );
			$where_clauses[] = "{$table}.date >= %d";
			$where_values    = array_merge( $where_values, array( $start_date ) );
		}

		if ( $args['end_date'] ) {
			$end_date        = absint( $args['end_date'] );
			$where_clauses[] = "{$table}.date < %d";
			$where_values    = array_merge( $where_values, array( $end_date ) );
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
		// Assemble and run the query.

		$sql .= $join . $where . $order_by . $limit . $offset;

		/**
		 * WordPress.DB.PreparedSQL.NotPrepared explained.
		 *
		 * The sniff isn't smart enough to follow $sql variable back to its source. So it doesn't know whether the query in $sql incorporates user-supplied values or not.
		 * Whitelisting comment is the solution here. @see https://github.com/WordPress/WordPress-Coding-Standards/issues/469
		 */
		$db = $wpdb;
		if ( $args['count'] ) {
			if ( empty( $where_values ) ) {
				$count = absint( $db->get_var( $sql ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$count = absint( $db->get_var( $db->prepare( $sql, $where_values ) ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			return $count;
		} elseif ( empty( $where_values ) ) {
				$results = $db->get_results( $sql ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$results = $db->get_results( $db->prepare( $sql, $where_values ) ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		}

		unset( $db );

		if ( empty( $results ) ) {
			return array();
		}

		$a = array();

		if ( 'objects' === $args['return'] || 'existing_objects' === $args['return'] ) {
			foreach ( $results as $result ) {
				$a[] = self::get( $result->id );
			}
		} elseif ( 'ids' === $args['return'] ) {
			foreach ( $results as $result ) {
				$a[] = $result->id;
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
	 * @param  mixed $activity Activity.
	 * @return false|WC_BIS_Activity_Data
	 */
	public function get( $activity ) {

		if ( is_numeric( $activity ) ) {
			$activity = absint( $activity );
			$activity = new WC_BIS_Activity_Data( $activity );
		} elseif ( $activity instanceof WC_BIS_Activity_Data ) {
			$activity = new WC_BIS_Activity_Data( $activity );
		} elseif ( is_object( $activity ) ) {
			$activity = new WC_BIS_Activity_Data( (array) $activity );
		} else {
			$activity = false;
		}

		if ( ! $activity || ! is_object( $activity ) || ! $activity->get_id() ) {
			return false;
		}

		return $activity;
	}

	/**
	 * Create a record in the DB.
	 *
	 * @param  array $args Array of arguments.
	 * @return false|int
	 *
	 * @throws Exception If invalid activity or arguments.
	 */
	public function add( $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'type'            => '',
				'product_id'      => 0,
				'notification_id' => 0,
				'user_id'         => 0,
				'user_email'      => '',
				'object_id'       => 0,
				'date'            => 0,
				'note'            => '',
			)
		);

		// Empty attributes.
		if ( empty( $args['type'] ) || empty( $args['notification_id'] ) || empty( $args['product_id'] ) ) {
			throw new Exception( esc_html__( 'Missing activity attributes.', 'woocommerce' ) );
		}

		$this->validate( $args );

		$activity = new WC_BIS_Activity_Data(
			array(
				'type'            => $args['type'],
				'product_id'      => $args['product_id'],
				'notification_id' => $args['notification_id'],
				'user_id'         => $args['user_id'],
				'user_email'      => $args['user_email'],
				'object_id'       => $args['object_id'],
				'date'            => $args['date'],
				'note'            => $args['note'],
			)
		);

		return $activity->save();
	}

	/**
	 * Update a record in the DB.
	 *
	 * @param  mixed $activity Activity.
	 * @param  array $args Array of arguments.
	 * @return bool
	 *
	 * @throws Exception If invalid activity or arguments.
	 */
	public function update( $activity, $args ) {

		if ( is_numeric( $activity ) ) {
			$activity = absint( $activity );
			$activity = new WC_BIS_Activity_Data( $activity );
		}

		if ( is_object( $activity ) && $activity->get_id() && ! empty( $args ) && is_array( $args ) ) {

			$this->validate( $args, $activity );

			$activity->set_all( $args );

			return $activity->save();
		}

		return false;
	}

	/**
	 * Validate data.
	 *
	 * @param  array                &$args Array of arguments.
	 * @param  WC_BIS_Activity_Data $activity Activity.
	 * @return void
	 *
	 * @throws Exception If invalid activity type or e-mail.
	 */
	public function validate( &$args, $activity = false ) {

		if ( ! empty( $args['type'] ) ) {
			if ( ! in_array( (string) $args['type'], array_keys( wc_bis_get_activity_types() ), true ) ) {
				throw new Exception( esc_html__( 'Invalid activity type.', 'woocommerce' ) );
			}
		}

		if ( ! empty( $args['user_email'] ) && ! filter_var( $args['user_email'], FILTER_VALIDATE_EMAIL ) ) {
			/* translators: %s email string */
			throw new Exception( esc_html( sprintf( __( 'Invalid e-mail: %s.', 'woocommerce' ), esc_html( $args['user_email'] ) ) ) );
		}

		// New Νotification.
		if ( ! is_object( $activity ) || ! $activity->get_id() ) {
			// Set timestamp.
			$args['date'] = time();
		}
	}

	/**
	 * Delete a record from the DB.
	 *
	 * @param  mixed $activity Activity.
	 * @return void
	 */
	public function delete( $activity ) {
		$activity = $this->get( $activity );
		if ( $activity ) {
			$activity->delete();
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
			SELECT DISTINCT YEAR( FROM_UNIXTIME( {$wpdb->prefix}woocommerce_bis_activity.`date` ) ) AS year, MONTH( FROM_UNIXTIME( {$wpdb->prefix}woocommerce_bis_activity.`date` ) ) AS month
			FROM {$wpdb->prefix}woocommerce_bis_activity
			ORDER BY {$wpdb->prefix}woocommerce_bis_activity.`date` DESC"
		);

		return $months;
	}

	/**
	 * Get activity data for given user.
	 *
	 * @since  1.0.10
	 *
	 * @param  int $user_id User ID.
	 * @param  int $limit (Optional).
	 * @param  int $offset (Optional).
	 * @return array
	 */
	public function get_activity_by_user( $user_id, $limit = 10, $offset = 0 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT a.* FROM {$wpdb->prefix}woocommerce_bis_notifications AS n
			INNER JOIN {$wpdb->prefix}woocommerce_bis_activity AS a ON n.id = a.notification_id
			WHERE n.user_id = %d
			AND a.type IN ( 'created', 'reactivated', 'deactivated', 'delivered' )
			ORDER BY a.`date` DESC
			LIMIT %d
			OFFSET %d;",
				$user_id,
				$limit,
				$offset
			)
		);

		if ( empty( $results ) ) {
			return array();
		}

		$activities = array();
		foreach ( $results as $result ) {
			$activities[] = self::get( $result->id );
		}

		return $activities;
	}

	/**
	 * Get total activity records for given user.
	 *
	 * @since  1.0.10
	 *
	 * @param  int $user_id User ID.
	 * @return int
	 */
	public function get_total_activity_records_by_user( $user_id ) {
		global $wpdb;

		$total_pages = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_bis_notifications AS n
			INNER JOIN {$wpdb->prefix}woocommerce_bis_activity AS a ON n.id = a.notification_id
			WHERE n.user_id = %d
			AND a.type IN ( 'created', 'reactivated', 'deactivated', 'delivered' )
			ORDER BY a.`date` DESC;",
				$user_id
			)
		);

		return absint( $total_pages );
	}
}
