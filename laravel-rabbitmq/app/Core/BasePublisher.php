<?php

namespace Package\Queues\Core;

use Illuminate\Contracts\Support\Jsonable;
use Package\PhpUtils\Dto\Dto;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\PhpUtils\Helpers\TokenHelper;
use Package\PhpUtils\Singletons\TracerSingleton;
use Package\Queues\Connectors\PackageConnector;
use Package\Queues\Enums\ExceptionEnum;
use Package\Queues\Enums\LogEnum;
use Package\Queues\Exchanges\BaseExchange;
use Package\Queues\Loggers\QueueLogger;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class BasePublisher
 * @package Package\Queues\Core
 */
abstract class BasePublisher
{
    /**
     * @var string
     */
    protected $exchangeClass;
    /**
     * @var BaseExchange
     */
    private $exchangeInstance;
    /**
     * @var QueueLogger
     */
    private $logger;

    private $delay = 0;

    /** @var null|string */
    private $connectionName = null;

    public function setDelay(int $delay): BasePublisher
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @param Dto $payload
     * @param int|null $priority
     * @param string $rountingKey
     * @throws BadImplementationException
     */
    public function publish(Dto $payload, ?int $priority = 0, string $rountingKey = ''): void
    {
        $exchangeLogPreffix = $this->exchange()->getLogTokenPrefix();
        $this->logger = new QueueLogger($exchangeLogPreffix);

        $this->exchange()->validateIfObjectInstanceOfDtoPayloadClass($payload);

        $message = $this->buildPublisherMessage($payload, $priority);

        $this->exchange()
            ->channel()
            ->basic_publish($message, $this->exchangeInstance->name(), $rountingKey);

        $this->logger->info(
            LogEnum::PUBLISHER_PUBLISHED_MESSAGE,
            [
                'dtoClass' => class_basename($payload),
                'dtoContent' => json_encode($payload),
                'priority' => $priority,
            ]
        );
    }

    /**
     * @param Dto $payload
     * @return AMQPMessage
     */
    private function buildPublisherMessage(Dto $payload, int $priority): AMQPMessage
    {
        $payloadString = $payload instanceof Jsonable
            ? $payload->toJson()
            : json_encode($payload);

        $traceValue = TracerSingleton::getTraceValue();
        $traceValue = ($traceValue == 'TRACE_NOT_IMPLEMENTED') || empty($traceValue)
            ? TokenHelper::generate(false)
            : $traceValue;
        $headers = ['tracer' => $traceValue,];

        if ($this->delay > 0) {
            $headers['x-delay'] = $this->delay;
        }
        $messageProperties = [
            'application_headers' => new AMQPTable($headers),
            'priority' => $priority,
        ];

        return new AMQPMessage($payloadString, $messageProperties);
    }

    /**
     * @return BaseExchange
     * @throws BadImplementationException
     */
    private function exchange(): BaseExchange
    {
        if (is_null($this->exchangeClass) || !class_exists($this->exchangeClass)) {
            throw new BadImplementationException(
                ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                'You must provide an exchange class path.'
            );
        }

        if (is_null($this->exchangeInstance) || !$this->exchangeInstance->channel()->is_open()) {
            $channel = PackageConnector::connection($this->connectionName)
                ->channel();
            $this->exchangeInstance = new $this->exchangeClass($channel);
            $this->exchangeInstance->create();
        }

        return $this->exchangeInstance;
    }

    /**
     * @param string|null $connectionName
     */
    public function setConnectionName(?string $connectionName): void
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function __destruct()
    {
        $this->exchangeInstance = null;
        $this->logger = null;
    }
}
