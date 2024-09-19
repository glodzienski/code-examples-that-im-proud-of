<?php

namespace Package\Queues\Example;

use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\Queues\Configurators\QueueDeclareConfigurator;
use Package\Queues\Core\BaseQueue;

/**
 * Class ExampleQueue
 * @package Package\Queues\Example
 */
class ExampleQueue extends BaseQueue
{
    /**
     * @var string
     */
    protected $exchangeClass = ExampleExchange::class;

    /**
     * ExampleQueue constructor.
     * @throws BadImplementationException
     */
    public function __construct()
    {
        $queueSettings = new QueueDeclareConfigurator();
        $queueSettings->attachValues(['name' => 'example',]);

        $this->configure($queueSettings);
    }
}
