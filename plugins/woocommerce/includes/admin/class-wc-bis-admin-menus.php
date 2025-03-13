<?php
/**
 * WC_BIS_Admin_Menus class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup BIS menus in WP admin.
 *
 * @version 2.0.2
 */
class WC_BIS_Admin_Menus {

	/**
	 * The CSS classes used to hide the submenu items in navigation.
	 *
	 * @var string
	 */
	protected static $HIDE_CSS_CLASS = 'hide-if-js';

	/**
	 * Setup.
	 */
	public static function init() {
		self::add_hooks();
	}

	/**
	 * Admin hooks.
	 */
	public static function add_hooks() {

		// Menu.
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10 );

		// Integrate WooCommerce breadcrumb bar.
		add_action( 'admin_menu', array( __CLASS__, 'wc_admin_connect_bis_pages' ) );
		add_filter( 'woocommerce_navigation_pages_with_tabs', array( __CLASS__, 'wc_admin_navigation_pages_with_tabs' ) );
		add_filter( 'woocommerce_navigation_page_tab_sections', array( __CLASS__, 'wc_admin_navigation_page_tab_sections' ) );
		add_filter( 'woocommerce_navigation_screen_ids', array( __CLASS__, 'wc_admin_navigation_screen_ids' ) );

		// Integrate WooCommerce side navigation.
		add_action( 'woocommerce_navigation_core_excluded_items', array( __CLASS__, 'exclude_navigation_items' ) );

		// Add "Activity" tab to WooCommerce status page.
		add_filter( 'woocommerce_admin_status_tabs', array( __CLASS__, 'add_bis_activity_page_tab' ) );
		add_action( 'woocommerce_admin_status_content_bis_activity', array( __CLASS__, 'activity_page' ) );
	}

	/**
	 * Configure back in stock tabs.
	 *
	 * @param  array $pages
	 * @return array
	 */
	public static function wc_admin_navigation_page_tab_sections( $pages ) {
		$pages['notifications'] = array( 'edit' );
		return $pages;
	}

	/**
	 * Configure back in stock page sections.
	 *
	 * @param  array $pages
	 * @return array
	 */
	public static function wc_admin_navigation_pages_with_tabs( $pages ) {
		$pages['bis_notifications'] = 'notifications';
		return $pages;
	}

	/**
	 * Add screen id to WooCommerce.
	 *
	 * @since 1.6.4
	 * @param  array $screen_ids  List of screen IDs.
	 * @return array
	 */
	public static function wc_admin_navigation_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, WC_BIS()->get_screen_ids() );

		return $screen_ids;
	}

	/**
	 * Connect pages with navigation bar.
	 *
	 * @return void
	 */
	public static function wc_admin_connect_bis_pages() {

		if ( function_exists( 'wc_admin_connect_page' ) ) {

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-bis_notifications',
					'screen_id' => 'woocommerce_page_bis_notifications-notifications',
					'title'     => __( 'Stock Notifications', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page' => 'bis_notifications',
						),
						'admin.php'
					),
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-bis_dashboard',
					'parent'    => 'woocommerce-bis_notifications',
					'screen_id' => 'woocommerce_page_bis_dashboard',
					'title'     => __( 'Dashboard', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page' => 'bis_dashboard',
						),
						'admin.php'
					),
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-bis_notifications-create',
					'parent'    => 'woocommerce-bis_notifications',
					'screen_id' => 'woocommerce_page_bis_notifications-notifications-create',
					'title'     => __( 'Add Notification', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page'         => 'bis_notifications',
							'section'      => 'create',
							'notification' => 1,
						),
						'admin.php'
					),
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-bis_notifications-edit',
					'parent'    => 'woocommerce-bis_notifications',
					'screen_id' => 'woocommerce_page_bis_notifications-notifications-edit',
					'title'     => __( 'Edit Notification', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page'         => 'bis_notifications',
							'section'      => 'edit',
							'notification' => 1,
						),
						'admin.php'
					),
				)
			);

		}
	}

	/**
	 * Renders tabs on our custom post types pages.
	 *
	 * @see includes/admin/views templates.
	 * @internal
	 */
	public static function render_tabs() {
		$screen = get_current_screen();

		// Handle tabs on the relevant WooCommerce pages.
		if ( $screen && ! in_array( $screen->id, WC_BIS()->get_screen_ids(), true ) ) {
			return;
		}

		$tabs = array();

		$tabs['dashboard'] = array(
			'title' => __( 'Dashboard', 'woocommerce' ),
			'url'   => admin_url( 'admin.php?page=bis_dashboard' ),
		);

		$tabs['notifications'] = array(
			'title' => __( 'Notifications', 'woocommerce' ),
			'url'   => admin_url( 'admin.php?page=bis_notifications' ),
		);

		$tabs = apply_filters( 'woocommerce_bis_admin_tabs', $tabs );

		if ( is_array( $tabs ) ) {
			?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php $current_tab = self::get_current_tab(); ?>
				<?php foreach ( $tabs as $tab_id => $tab ) : ?>
					<?php $class = $tab_id === $current_tab ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' ); ?>
					<?php printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $tab['url'] ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab['title'] ) ); ?>
				<?php endforeach; ?>
			</nav>
			<?php
		}
	}

	/**
	 * Returns the current admin tab.
	 *
	 * @param  string $current_tab (Optional)
	 * @return string
	 */
	public static function get_current_tab( $current_tab = false ) {

		// Default to Dashboard.
		if ( ! $current_tab ) {
			$current_tab = 'woocommerce_page_bis_dashboard';
		}

		$screen = get_current_screen();
		if ( $screen ) {
			if ( in_array( $screen->id, array( 'woocommerce_page_bis_dashboard' ), true ) ) {
				$current_tab = 'dashboard';
			} elseif ( in_array( $screen->id, array( 'woocommerce_page_bis_notifications' ), true ) ) {
				$current_tab = 'notifications';
			}
		}

		/**
		 * Filters the current Admin tab.
		 *
		 * @param  string    $current_tab
		 * @param  WP_Screen $screen
		 */
		return (string) apply_filters( 'woocommerce_bis_admin_current_tab', $current_tab, $screen );
	}

	/**
	 * Add menu items.
	 */
	public static function add_menu() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$dashboard_page = add_submenu_page(
			'woocommerce',
			__( 'Stock Notifications Dashboard', 'woocommerce' ),
			__( 'Stock Notifications', 'woocommerce' ),
			'manage_woocommerce',
			'bis_dashboard',
			array( __CLASS__, 'dashboard_page' )
		);

		$backinstock_page = add_submenu_page(
			'woocommerce',
			__( 'Notifications', 'woocommerce' ),
			__( 'Notifications', 'woocommerce' ),
			'manage_woocommerce',
			'bis_notifications',
			array( __CLASS__, 'backinstock_page' )
		);

		add_action( 'load-' . $backinstock_page, array( __CLASS__, 'backinstock_page_init' ) );

		// Hide pages.
		self::hide_submenu_page( 'woocommerce', 'bis_notifications' );
	}

	/**
	 * Add "Activity" tab to WooCommerce status page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function add_bis_activity_page_tab( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			return $tabs;
		}

		$tabs['bis_activity'] = __( 'Customer stock notifications', 'woocommerce' );
		return $tabs;
	}

	/**
	 * Render "Dashboard" page.
	 */
	public static function dashboard_page() {
		WC_BIS_Admin_Dashboard_Page::output();
	}

	/**
	 * Render "Back In Stock" page.
	 */
	public static function backinstock_page() {

		// Select section.
		$section = '';

		if ( isset( $_GET['section'] ) ) {
			$section = wc_clean( $_GET['section'] );
		}

		switch ( $section ) {
			case 'delete':
				WC_BIS_Admin_Notifications_Page::delete();
				break;
			case 'create':
				WC_BIS_Admin_Notifications_Page::create_output();
				break;
			case 'edit':
				WC_BIS_Admin_Notifications_Page::edit_output();
				break;
			default:
				WC_BIS_Admin_Notifications_Page::output();
				break;
		}
	}

	/**
	 * Init admin page. Setups the `save` feature and adds messages.
	 */
	public static function backinstock_page_init() {

		WC_BIS_Admin_Notifications_Page::process();

		do_action( 'woocommerce_bis_notifications_page_init' );
	}

	/**
	 * Render "Activity" page.
	 */
	public static function activity_page() {
		WC_BIS_Admin_Activity_Page::output();
	}

	/**
	 * Exclude menu items from WooCommerce menu migration.
	 *
	 * This feature has been deprecated in WooCommerce 9.5, so it can be removed in the future.
	 * Keeping this here for now.
	 *
	 * @since  1.0.9
	 *
	 * @param  array $excluded_items
	 * @return array
	 */
	public static function exclude_navigation_items( $excluded_items ) {
		$excluded_items[] = 'bis_activity';
		$excluded_items[] = 'bis_notifications';
		$excluded_items[] = 'bis_dashboard';

		return $excluded_items;
	}

	/**
	 * Hide the submenu page based on slug and return the item that was hidden.
	 *
	 * @since 1.6.3
	 *
	 * Instead of actually removing the submenu item, a safer approach is to hide it and filter it in the API response.
	 * In this manner we'll avoid breaking third-party plugins depending on items that no longer exist.
	 *
	 * @param string $menu_slug The parent menu slug.
	 * @param string $submenu_slug The submenu slug that should be hidden.
	 * @return false|array
	 */
	protected static function hide_submenu_page( $menu_slug, $submenu_slug ) {
		global $submenu;

		if ( ! isset( $submenu[ $menu_slug ] ) ) {
			return false;
		}

		foreach ( $submenu[ $menu_slug ] as $i => $item ) {
			if ( $submenu_slug !== $item[2] ) {
				continue;
			}

			self::hide_submenu_element( $i, $menu_slug, $item );

			return $item;
		}

		return false;
	}

	/**
	 * Apply the hide-if-js CSS class to a submenu item.
	 *
	 * @since 1.6.3
	 *
	 * @param int    $index The position of a submenu item in the submenu array.
	 * @param string $parent_slug The parent slug.
	 * @param array  $item The submenu item.
	 */
	protected static function hide_submenu_element( $index, $parent_slug, $item ) {
		global $submenu;

		$css_classes = empty( $item[4] ) ? self::$HIDE_CSS_CLASS : $item[4] . ' ' . self::$HIDE_CSS_CLASS;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ $parent_slug ][ $index ][4] = $css_classes;
	}
}

WC_BIS_Admin_Menus::init();
