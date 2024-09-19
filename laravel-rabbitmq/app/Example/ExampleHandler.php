<?php

namespace Package\Queues\Example;

use Package\Queues\Contracts\HandlerContract;
use Package\Queues\Dtos\ExamplePayloadDto;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class ExampleHandler
 * @package Package\Queues\Example
 */
class ExampleHandler implements HandlerContract
{
    protected $baseConsumer;

    public function __construct(BaseConsumer $baseConsumer = null)
    {
        if ($baseConsumer) {
            $this->baseConsumer = $baseConsumer;
        }
    }

    /**
     * @param ExamplePayloadDto $dto
     * @param AMQPMessage $message
     */
    public function handle(ExamplePayloadDto $dto, AMQPMessage $message): void
    {
        echo json_encode($dto);
        echo json_encode($message);

        $message->ack();
    }
}
