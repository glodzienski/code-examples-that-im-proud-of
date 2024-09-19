<?php

namespace Package\Queues\Example;

use Package\Queues\Core\BasePublisher;

/**
 * Class ExamplePublisher
 * @package Package\Queues\Example
 */
class ExamplePublisher extends BasePublisher
{
    /**
     * @var string
     */
    protected $exchangeClass = ExampleExchange::class;
}
