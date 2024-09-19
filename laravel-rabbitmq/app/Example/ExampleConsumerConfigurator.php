<?php

namespace Package\Queues\Example;

use Package\Queues\Consumers\ExampleConsumer;
use Package\Queues\Core\BaseConsumerConfigurator;

/**
 * Class ExampleConsumerConfigurator
 * @package Package\Queues\Example
 */
class ExampleConsumerConfigurator extends BaseConsumerConfigurator
{
    /**
     * @var string
     */
    public $consumerClass = ExampleConsumer::class;
    /**
     * @var string
     */
    public $consumerHandlerClass = ExampleHandler::class;
    /**
     * @var int
     */
    public $quantity = 1;
    /**
     * @var int
     */
    public $lifetime = 30;
}
