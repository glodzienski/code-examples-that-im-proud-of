<?php

namespace Package\Queues\Example;

use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\Queues\Core\BaseExchange;
use Package\Queues\Configurators\ExchangeDeclareConfigurator;
use Package\Queues\Enums\ExchangeTypeEnum;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class ExampleExchange
 * @package Package\Queues\Example
 */
class ExampleExchange extends BaseExchange
{
    /** @var string */
    protected $dtoPayloadClass = ExamplePayloadDto::class;

    /** @var string */
    protected $logTokenPrefix = 'EXAMPLE';

    /**
     * ExampleExchange constructor.
     * @param AMQPChannel $channel
     * @throws BadImplementationException
     */
    public function __construct(AMQPChannel $channel)
    {
        parent::__construct($channel);

        $exchangeSettings = new ExchangeDeclareConfigurator();
        $exchangeSettings->attachValues([
            'name' => 'example',
            'type' => ExchangeTypeEnum::DIRECT,
        ]);
        $this->configure($exchangeSettings);
    }
}
