<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Queue;

use Automattic\WooCommerce\Enums\ActionQueuePriority;

/**
 * This interface represents a queue supporting priorities. Please note that priorities enum is specific to action
 * scheduled library, and in case of alternative implementations you might need to introduce a new enum.
 */
interface QueueWithPrioritiesInterface extends \WC_Queue_Interface {
	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook     The hook to trigger.
	 * @param array  $args     Arguments to pass when the hook triggers.
	 * @param string $group    The group to assign this job to.
	 * @param int    $priority Action priority.
	 * @return int The action ID
	 */
	public function add( $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL );

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 * @param int    $priority  Action priority.
	 * @return int The action ID
	 */
	public function schedule_single( $timestamp, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL );

	/**
	 * Schedule a recurring action
	 *
	 * @param int    $timestamp           When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook                The hook to trigger.
	 * @param array  $args                Arguments to pass when the hook triggers.
	 * @param string $group               The group to assign this job to.
	 * @param int    $priority            Action priority.
	 * @return int The action ID
	 */
	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL );

	/**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @param int    $timestamp     The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-link schedule string.
	 * @param string $hook          The hook to trigger.
	 * @param array  $args          Arguments to pass when the hook triggers.
	 * @param string $group         The group to assign this job to.
	 * @param int    $priority      Action priority.
	 * @return int The action ID
	 */
	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL );
}
