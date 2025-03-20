<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\Queue;

/**
 *  This interface represents `\WC_Queue_Interface` from legacy components namespace.
 *  It mainly serves to provide clarity on the domain structure in this namespace.
 */
interface QueueFifoInterface extends \WC_Queue_Interface {
}
