<?php

namespace Package\Queues\Core;

use Exception;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\Queues\Connectors\PackageConnector;
use Package\Queues\Configurators\QueueDeclareConfigurator;
use Package\Queues\Enums\ExceptionEnum;
use Package\Queues\Exchanges\BaseExchange;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class BaseQueue
 * @package Package\Queues\Core
 */
abstract class BaseQueue
{
    /**
     * @var string
     * @required
     */
    protected $exchangeClass;
    /**
     * @var QueueDeclareConfigurator
     */
    private $queueSettings;
    /**
     * @var AMQPChannel
     */
    private $channel;
    /**
     * @var BaseExchange
     */
    private $exchangeInstance;

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        if (isset($this->channel)) {
            $this->channel->close();
            $this->channel = null;
        }
        $this->exchangeInstance = null;
    }

    /**
     *
     * @throws BadImplementationException
     */
    public function create(): void
    {
        $this->validateConfiguration();

        $channel = $this->channel();
        $this->exchange()->create();

        $channel
            ->queue_declare(
                $this->queueSettings->name,
                $this->queueSettings->passive,
                $this->queueSettings->durable,
                $this->queueSettings->exclusive,
                $this->queueSettings->autoDelete,
                $this->queueSettings->nowait,
                $this->queueSettings->arguments,
                $this->queueSettings->ticket
            );

        $channel->queue_bind($this->queueSettings->name, $this->exchange()->name(), $this->queueSettings->routingKey);
    }

    /**
     * @return void
     * @throws BadImplementationException
     */
    private function validateConfiguration(): void
    {
        $queueSettingsNotDeclared = is_null($this->queueSettings);
        $queueSettingsRequiredPropsNotConfigured = empty($this->queueSettings->name);
        if ($queueSettingsNotDeclared || $queueSettingsRequiredPropsNotConfigured) {
            throw new BadImplementationException(
                ExceptionEnum::QUEUE_BAD_IMPLEMENTATION,
                'You must configure your queue, use "configure" method.'
            );
        }
    }

    /**
     * @return AMQPChannel
     * @throws BadImplementationException
     */
    public function channel(): AMQPChannel
    {
        if (is_null($this->channel)) {
            $this->channel = PackageConnector::connection()->channel();
        }

        return $this->channel;
    }

    /**
     * @return BaseExchange
     * @throws BadImplementationException
     */
    public function exchange(): BaseExchange
    {
        if (is_null($this->exchangeInstance)) {
            $exchangeClassNotDeclared = is_null($this->exchangeClass);
            $exchangeClassNotExists = !class_exists($this->exchangeClass);
            if ($exchangeClassNotDeclared || $exchangeClassNotExists) {
                throw new BadImplementationException(
                    ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                    'Your consumer’s Exchange class has some problems.'
                );
            }

            $exchangeInstance = new $this->exchangeClass($this->channel());

            $exchangeClassNotInstanceOfBaseExchange = !($exchangeInstance instanceof BaseExchange);
            if ($exchangeClassNotInstanceOfBaseExchange) {
                throw new BadImplementationException(
                    ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                    'Your consumer’s Exchange class has some problems.'
                );
            }

            $this->exchangeInstance = $exchangeInstance;
        }

        return $this->exchangeInstance;
    }

    /**
     * @return string
     * @throws BadImplementationException
     */
    public function name(): string
    {
        $this->validateConfiguration();

        return $this->queueSettings->name;
    }

    /**
     * @param QueueDeclareConfigurator $queueDeclareParametersDto
     * @return void
     */
    protected function configure(QueueDeclareConfigurator $queueDeclareParametersDto): void
    {
        $this->queueSettings = $queueDeclareParametersDto;
    }
}
