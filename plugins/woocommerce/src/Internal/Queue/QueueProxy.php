<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Queue;

use Automattic\WooCommerce\Enums\ActionQueuePriority;

/**
 * This class aggregates the queue instance and allows iteracting with queues in backward-compatible way.
 *
 * Supported instances:
 * - customized instances (see `woocommerce_queue_class` filter)
 * - previous default queue operating in FIFO mode (see `\WC_Action_Queue`)
 * - currently used default queue supporting priorities (see `DefaultQueueWithPriorities`)
 */
final class QueueProxy implements QueueWithPrioritiesInterface
{
	/**
	 * @var DefaultQueueFifo|null
	 */
	private $queue_fifo_instance;

	/**
	 * @var DefaultQueueWithPriorities|null
     */
	private $queue_with_priorities_instance;

	/**
	 * @param \WC_Queue_Interface $queue_instance
	 */
	public function __construct( $queue_instance ) {
		if ( $queue_instance instanceof QueueWithPrioritiesInterface) {
			$this->queue_with_priorities_instance = $queue_instance;
		} else {
			$this->queue_fifo_instance = $queue_instance;
		}
	}

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook     The hook to trigger.
	 * @param array  $args     Arguments to pass when the hook triggers.
	 * @param string $group    The group to assign this job to.
	 * @param int    $priority Priority, if supported by the queue. See \Automattic\WooCommerce\Enums\ActionQueuePriority for possible options.
	 * @return int The action ID.
	 */
	public function add($hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL)
	{
		return $this->queue_with_priorities_instance
			? $this->queue_with_priorities_instance->add( $hook, $args, $group, $priority  )
			: $this->queue_fifo_instance->add( $hook, $args, $group );
	}

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 * @param int    $priority  Priority, if supported by the queue. See \Automattic\WooCommerce\Enums\ActionQueuePriority for possible options.
	 * @return int The action ID.
	 */
	public function schedule_single($timestamp, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL)
	{
		return $this->queue_with_priorities_instance
			? $this->queue_with_priorities_instance->schedule_single( $timestamp, $hook, $args, $group, $priority )
			: $this->queue_fifo_instance->schedule_single( $timestamp, $hook, $args, $group );
	}

	/**
	 * Schedule a recurring action
	 *
	 * @param int    $timestamp           When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook                The hook to trigger.
	 * @param array  $args                Arguments to pass when the hook triggers.
	 * @param string $group               The group to assign this job to.
	 * @param int    $priority            Priority, if supported by the queue. See \Automattic\WooCommerce\Enums\ActionQueuePriority for possible options.
	 * @return int The action ID.
	 */
	public function schedule_recurring($timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL)
	{
		return $this->queue_with_priorities_instance
			? $this->queue_with_priorities_instance->schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args, $group, $priority  )
			: $this->queue_fifo_instance->schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args, $group );
	}

	/**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @param int    $timestamp     The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-link schedule string.
	 * @param string $hook          The hook to trigger.
	 * @param array  $args          Arguments to pass when the hook triggers.
	 * @param string $group         The group to assign this job to.
	 * @param int    $priority      Priority, if supported by the queue. See \Automattic\WooCommerce\Enums\ActionQueuePriority for possible options.
	 * @return int The action ID
	 */
	public function schedule_cron($timestamp, $cron_schedule, $hook, $args = array(), $group = '', int $priority = ActionQueuePriority::NORMAL)
	{
		return $this->queue_with_priorities_instance
			? $this->queue_with_priorities_instance->schedule_cron( $timestamp, $cron_schedule, $hook, $args, $group, $priority )
			: $this->queue_fifo_instance->schedule_cron( $timestamp, $cron_schedule, $hook, $args, $group );
	}

	/**
	 * Dequeue the next scheduled instance of an action with a matching hook (and optionally matching args and group).
	 *
	 * Any recurring actions with a matching hook should also be cancelled, not just the next scheduled action.
	 *
	 * While technically only the next instance of a recurring or cron action is unscheduled by this method, that will also
	 * prevent all future instances of that recurring or cron action from being run. Recurring and cron actions are scheduled
	 * in a sequence instead of all being scheduled at once. Each successive occurrence of a recurring action is scheduled
	 * only after the former action is run. As the next instance is never run, because it's unscheduled by this function,
	 * then the following instance will never be scheduled (or exist), which is effectively the same as being unscheduled
	 * by this method also.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to (if any).
	 */
	public function cancel($hook, $args = array(), $group = '')
	{
		( $this->queue_with_priorities_instance ?? $this->queue_fifo_instance )->cancel( $hook, $args, $group );
	}

	/**
	 * Dequeue all actions with a matching hook (and optionally matching args and group) so no matching actions are ever run.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to (if any).
	 */
	public function cancel_all($hook, $args = array(), $group = '')
	{
		( $this->queue_with_priorities_instance ?? $this->queue_fifo_instance )->cancel_all( $hook, $args, $group );
	}

	/**
	 * Get the date and time for the next scheduled occurrence of an action with a given hook
	 * (an optionally that matches certain args and group), if any.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Filter to a hook with matching args that will be passed to the job when it runs.
	 * @param string $group Filter to only actions assigned to a specific group.
	 * @return \WC_DateTime|null The date and time for the next occurrence, or null if there is no pending, scheduled action for the given hook.
	 */
	public function get_next($hook, $args = null, $group = '')
	{
		return ( $this->queue_with_priorities_instance ?? $this->queue_fifo_instance )->get_next( $hook, $args, $group );
	}

	/**
	 * Find scheduled actions
	 *
	 * @param array  $args Possible arguments, with their default values:
	 *        'hook' => '' - the name of the action that will be triggered
	 *        'args' => null - the args array that will be passed with the action
	 *        'date' => null - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
	 *        'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
	 *        'modified' => null - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
	 *        'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
	 *        'group' => '' - the group the action belongs to
	 *        'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
	 *        'claimed' => null - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
	 *        'per_page' => 5 - Number of results to return
	 *        'offset' => 0
	 *        'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'
	 *        'order' => 'ASC'.
	 *
	 * @param string $return_format OBJECT, ARRAY_A, or ids.
	 * @return array
	 */
	public function search($args = array(), $return_format = OBJECT)
	{
		return ( $this->queue_with_priorities_instance ?? $this->queue_fifo_instance )->search( $args, $return_format );
	}
}
