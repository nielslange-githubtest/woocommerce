<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Queue;

/**
 * This class represents `\WC_Action_Queue` from legacy components namespace.
 * It mainly serves to provide clarity on the domain structure in this namespace.
 */
final class DefaultQueueFifo extends \WC_Action_Queue implements QueueFifoInterface {
}
