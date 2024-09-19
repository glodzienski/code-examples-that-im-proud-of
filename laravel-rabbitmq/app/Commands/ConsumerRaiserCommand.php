<?php

namespace Package\Queues\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\Queues\Consumers\BaseConsumer;
use Package\Queues\Contracts\HandlerContract;
use Package\Queues\Enums\ExceptionEnum;
use ReflectionException;
use Throwable;

/**
 * Class ConsumerRaiserCommand
 * @package Package\Queues\Commands
 */
class ConsumerRaiserCommand extends Command
{
    public $consumers = [];
    public $handlers = [];

    /** @var string */
    protected $signature = "queue:consumer {--consumer= : Consumer Class}
                                           {--handler= : Consumer Handler Class}
                                           {--clone= : Consumer Clone Number}
                                           {--lifetime=240 : Consumer lifetime in minutes}
                                           {--payload= : Cache Key of Your Payload}";

    /**
     * @var string
     */
    protected $description = "Commando que levanta o consumer passado.";

    protected $payloadCacheKey;

    public function getHandlerInstance(string $consumerHandlerClass, BaseConsumer $baseConsumer): HandlerContract
    {
        if (is_null($this->handlers[$consumerHandlerClass] ?? null)) {
            $handler = new $consumerHandlerClass($baseConsumer);
            throw_if(
                !($handler instanceof HandlerContract),
                new BadImplementationException(
                    ExceptionEnum::CONSUMER_RAISER_BAD_IMPLEMENTATION,
                    'Consumer Handler class must implement HandlerContract.'
                )
            );

            $this->handlers[$consumerHandlerClass] = $handler;
        }

        return $this->handlers[$consumerHandlerClass];
    }

    private function getConsumerInstance(string $consumerClass, array $payload): BaseConsumer
    {
        if (is_null($this->consumers[$consumerClass] ?? null)) {
            $consumer = new $consumerClass($payload);
            throw_if(
                !($consumer instanceof BaseConsumer),
                new BadImplementationException(
                    ExceptionEnum::CONSUMER_RAISER_BAD_IMPLEMENTATION,
                    'Consumer class must instance of BaseConsumer.'
                )
            );

            $this->consumers[$consumerClass] = $consumer;
        }

        return $this->consumers[$consumerClass];
    }

    /**
     * @throws BadImplementationException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function handle(): void
    {
        $consumerClass = $this->option('consumer');
        $this->validateConsumerClassExists($consumerClass);

        $consumerHandlerClass = $this->option('handler');
        $this->validateConsumerHandlerClass($consumerHandlerClass);

        $lifetime = abs($this->option('lifetime'));

        $this->payloadCacheKey = $this->option('payload') ?? '';
        $payload = Cache::get($this->payloadCacheKey) ?? [];
        if (!is_null($payload)) {
            $this->validateConsumerPayload($payload);
        }

        $consumer = $this->getConsumerInstance($consumerClass, $payload);
        $handler = $this->getHandlerInstance($consumerHandlerClass, $consumer);

        $consumer
            ->setHandler($handler)
            ->lifetime($lifetime)
            ->consume();
    }

    /**
     * @param string $consumerClass
     * @return void
     * @throws Throwable
     */
    private function validateConsumerClassExists(string $consumerClass): void
    {
        throw_if(
            !class_exists($consumerClass),
            new BadImplementationException(
                ExceptionEnum::CONSUMER_RAISER_BAD_IMPLEMENTATION,
                'Consumer class not found.'
            )
        );
    }

    /**
     * @param string $consumerHandlerClass
     * @throws Throwable
     */
    private function validateConsumerHandlerClass(string $consumerHandlerClass): void
    {
        throw_if(
            !class_exists($consumerHandlerClass),
            new BadImplementationException(
                ExceptionEnum::CONSUMER_RAISER_BAD_IMPLEMENTATION,
                'Consumer Handler class not found.'
            )
        );
    }

    /**
     * @param array|null $payload
     * @throws Throwable
     */
    private function validateConsumerPayload(?array $payload): void
    {
        throw_if(
            !is_array($payload),
            new BadImplementationException(
                ExceptionEnum::CONSUMER_PAYLOAD_MUST_BE_ARRAY,
                'Consumer Payload must be an array.'
            )
        );
    }

    public function __destruct()
    {
        if ($this->payloadCacheKey) {
            Cache::forget($this->payloadCacheKey);
        }
        unset($this->handlers, $this->consumers);
    }
}
