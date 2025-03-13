<?php

defined( 'ABSPATH' ) || exit;

class WC_BIS_Noop {
	public static WC_Logger_Interface $logger;

	public function __construct() {
		$this->logger = wc_get_logger();
	}

	public function __call( $method, $args ) {
		$this->logger->debug( 'Back In Stock Notifications are disabled and something tried to call its method ' . $method . '()' );
	}

	public static function __callStatic( $method, $args ) {
		self::$logger->debug( 'Back In Stock Notifications are disabled and something tried to call its static method ' . $method . '()' );
	}
}
