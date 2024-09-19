<?php

namespace Package\Queues\Example;

use Package\Queues\Configurators\ConsumerDeclareConfigurator;
use Package\Queues\Core\BaseConsumer;

/**
 * Class ExampleConsumer
 * @package Package\Queues\Example
 */
class ExampleConsumer extends BaseConsumer
{
    /**
     * @var string
     */
    protected $queueClass = ExampleQueue::class;

    public function __construct(array $payload = [])
    {
        parent::__construct($this, $payload);

        $consumerSettings = new ConsumerDeclareConfigurator();
        $consumerSettings->attachValues([
            'consumerTagPrefix' => 'exemplo',
        ]);
        $this->configure($consumerSettings);
    }
}
