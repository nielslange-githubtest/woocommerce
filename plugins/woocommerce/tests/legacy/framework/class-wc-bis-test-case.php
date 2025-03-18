<?php
/**
 * BIS test case
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

declare( strict_types=1 );

/**
 * BIS test case class.
 */
class WC_BIS_Test_Case extends WC_Unit_Test_Case {

	/**
	 * No need to strip newlines and tabs when using expectedOutputString().
	 *
	 * @param  string $output Output string.
	 * @return string
	 */
	public function filter_output( $output ) {
		return $output;
	}

	/**
	 * Runs the pending batch.
	 *
	 * @return void
	 */
	protected function sync_process_notifications_batch() {

		$search = as_get_scheduled_actions(
			array(
				'hook'   => 'wc_bis_process_notifications_batch',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			),
			OBJECT
		);

		$queue_runner = new ActionScheduler_QueueRunner();

		foreach ( $search as $job_id => $job ) {
			$queue_runner->process_action( $job_id );
		}
	}

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		WC_BIS_Test_Helper::enable_feature();
	}

	/**
	 * Clean up after test case.
	 */
	public function tearDown(): void {
		WC_BIS_Test_Helper::reset_feature();
		parent::tearDown();
	}
}
