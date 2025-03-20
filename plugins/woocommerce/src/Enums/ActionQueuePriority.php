<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for scheduling actions with action scheduler based queue implementations.
 */
final class ActionQueuePriority {
	/**
	 * For the tasks which should be picked from the queue first. Use wisely
	 *
	 * @var int
	 */
	public const URGENT = 0;

	/**
	 * For the tasks with high/elevated priority. Value chosen as NORMAL^0;
	 *
	 * @var int
	 */
	public const HIGH = 1;

	/**
	 * For the tasks with normal priority. Value chosen as NORMAL^1;
	 *
	 * @var int
	 */
	public const NORMAL = 10;

	/**
	 * For the tasks with normal priority. Value chosen as NORMAL^2;
	 *
	 * @var int
	 */
	public const LOW = 100;
}
