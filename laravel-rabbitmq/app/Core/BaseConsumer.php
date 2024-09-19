<?php

namespace Package\Queues\Core;

use Carbon\Carbon;
use Closure;
use Exception;
use Package\PhpUtils\Dto\Dto;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\PhpUtils\Helpers\TokenHelper;
use Package\PhpUtils\Singletons\TracerSingleton;
use Package\Queues\Configurators\ConsumerDeclareConfigurator;
use Package\Queues\Connectors\PackageConnector;
use Package\Queues\Contracts\HandlerContract;
use Package\Queues\Enums\ExceptionEnum;
use Package\Queues\Enums\LogEnum;
use Package\Queues\Loggers\QueueLogger;
use Package\Queues\Queues\BaseQueue;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Class BaseConsumer
 * @package Package\Queues\Core
 */
abstract class BaseConsumer
{
    /**
     * @var string
     */
    protected $queueClass;
    /**
     * @var BaseQueue
     */
    protected $queueInstance;
    /**
     * @var BaseConsumer
     */
    protected $consumerInstance;
    /**
     * @var array
     */
    protected $payload;
    /**
     * @var HandlerContract
     */
    protected $handler;
    /**
     * @var int
     */
    protected $lifetime = 240;
    /**
     * @var QueueLogger
     */
    protected $logger;
    /**
     * @var ConsumerDeclareConfigurator
     */
    protected $consumerSettings;

    /**
     * BaseConsumer constructor.
     */
    public function __construct(BaseConsumer $consumerInstance = null, array $payload = [])
    {
        $exchangeLogPreffix = $this->queue()->exchange()->getLogTokenPrefix();
        $this->logger = new QueueLogger($exchangeLogPreffix);
        $this->configureTracerValue();

        $this->payload = $payload;
        if ($consumerInstance) {
            $this->consumerInstance = $consumerInstance;
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function consume(): void
    {
        $this->validateConfiguration();

        $this->logger->info(LogEnum::CONSUMER_EXECUTOR_START, ['Consumer Executor Start',]);
        try {
            $consumerCallback = $this->getConsumerCallbackBaseFunction();

            $consumerChannel = $this->queue()->channel();
            $consumerChannel->basic_qos(0, $this->consumerSettings->prefetchSize, false);

            $consumerChannel->basic_consume(
                $this->queue()->name(),
                $this->buildConsumerTag(),
                $this->consumerSettings->noLocal,
                $this->consumerSettings->noAck,
                $this->consumerSettings->exclusive,
                $this->consumerSettings->noWait,
                $consumerCallback
            );

            $consumerLifetimeTimestamp = Carbon::now()->addMinutes($this->lifetime)->getTimestamp();
            $timeoutInSeconds = $this->lifetime * 60;
            while ($consumerChannel->is_open()) {
                try {
                    $carbonInstance = Carbon::now();
                    if ($carbonInstance->getTimestamp() > $consumerLifetimeTimestamp) {
                        $consumerChannel->close();

                        return;
                    }

                    $consumerChannel->wait(
                        null,
                        false,
                        $timeoutInSeconds
                    );
                } finally {
                    unset($carbonInstance);
                }
            }
        } catch (AMQPTimeoutException $exception) {
            // do nothing
        } catch (Exception | Throwable $exception) {
            $this->logger->severe(ExceptionEnum::CONSUMER_EXECUTOR_WITH_ERROR, [], $exception);
        } finally {
            PackageConnector::shutdownAll();
            $this->logger->info(LogEnum::CONSUMER_EXECUTOR_END, ['Consumer Executor End',]);
        }
    }

    /**
     * @param HandlerContract $handler
     * @return $this
     * @throws BadImplementationException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setHandler(HandlerContract $handler): BaseConsumer
    {
        $this->validateHandler($handler);
        $this->handler = $handler;

        return $this;
    }

    /**
     * @param HandlerContract $handler
     * @throws BadImplementationException
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function validateHandler(HandlerContract $handler): void
    {
        $reflection = new ReflectionClass($handler);

        $handlerDoesNotHaveHandleMethod = !$reflection->hasMethod('handle');
        throw_if(
            $handlerDoesNotHaveHandleMethod,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'Your Handler must have a handle() method. See ExampleHandler.'
            )
        );

        $handlerHandleMethod = $reflection->getMethod('handle');

        $handleMethodIsNotPublic = !$handlerHandleMethod->isPublic();
        throw_if(
            $handleMethodIsNotPublic,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'Your handle method must be public. See ExampleHandler.'
            )
        );

        $handleMethodParameters = $handlerHandleMethod->getParameters();

        $firstParameter = $handleMethodParameters[0];
        $firstParameterInstance = $firstParameter->getClass()->newInstanceWithoutConstructor();
        $firstParameterDoesNotInstanceOfDto = !($firstParameterInstance instanceof Dto);
        throw_if(
            $firstParameterDoesNotInstanceOfDto,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'The first parameter of handle method, must instance of type Dto. See ExampleHandler.'
            )
        );

        $this->queue()->exchange()->validateIfObjectInstanceOfDtoPayloadClass($firstParameterInstance);

        $secondParameter = $handleMethodParameters[1] ?? null;
        if (is_null($secondParameter)) {
            return;
        }

        $secondParameterDoesNotInstanceOfAMQPMessage = !$secondParameter->hasType();
        throw_if(
            $secondParameterDoesNotInstanceOfAMQPMessage,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'The second parameter of handle method, must instance of type AMQPMessage. See ExampleHandler.'
            )
        );

        $secondParameterInstance = $secondParameter->getClass()->newInstanceWithoutConstructor();
        $secondParameterDoesNotInstanceOfAMQPMessage = !($secondParameterInstance instanceof AMQPMessage);
        throw_if(
            $secondParameterDoesNotInstanceOfAMQPMessage,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'The second parameter of handle method, must instance of type AMQPMessage. See ExampleHandler.'
            )
        );
    }

    /**
     * @return BaseQueue
     * @throws BadImplementationException
     */
    protected function queue(): BaseQueue
    {
        if (is_null($this->queueInstance)) {
            $this->queueInstance = new $this->queueClass();
            $this->queueInstance->create();
        }

        return $this->queueInstance;
    }

    /**
     * @param int $lifetime
     * @return $this
     */
    public function lifetime(int $lifetime): BaseConsumer
    {
        $this->lifetime = abs($lifetime);

        return $this;
    }

    /**
     * @return Closure
     */
    protected function getConsumerCallbackBaseFunction(): Closure
    {
        return function (AMQPMessage $message) {
            try {
                $messageRawBody = $message->getBody();

                $messageProperties = $message->get_properties();
                $this->configureTracerValue($messageProperties);

                $messageTreatedBody = json_decode($messageRawBody, true) ?? [];
                $this->logger->info(
                    LogEnum::CONSUMER_HANDLER_START,
                    ['title' => 'Consumer handler start', 'message' => $messageTreatedBody]
                );

                $messageDtoClass = $this->queue()->exchange()->getDtoPayloadClass();
                $messageDtoInstance = new $messageDtoClass();
                $messageDtoInstance->attachValues($messageTreatedBody);

                $handler = $this->handler;
                $handler->handle($messageDtoInstance, $message);
            } catch (Throwable $e) {
                $message->reject();
                $this->logger->error(ExceptionEnum::CONSUMER_HANDLER_WITH_ERROR, [], $e);
            } finally {
                $this->logger->info(
                    LogEnum::CONSUMER_HANDLER_END,
                    ['title' => 'Consumer handler end',]
                );

                unset($messageDtoInstance);
            }
        };
    }

    /**
     * @param array $messageProperties
     * @return void
     */
    private function configureTracerValue(array $messageProperties = []): void
    {
        $messageTracerProperty = $messageProperties['application_headers']['tracer'] ?? null;
        $tracerValue = $messageTracerProperty ?? TokenHelper::generate(false);

        TracerSingleton::setTraceValue($tracerValue);
    }

    /**
     * @throws Throwable
     */
    protected function validateConfiguration(): void
    {
        $classSettingsNotExists = is_null($this->consumerSettings);
        $classSettingsRequiredPropsNotConfigured = empty($this->consumerSettings->consumerTagPrefix);
        throw_if(
            $classSettingsNotExists || $classSettingsRequiredPropsNotConfigured,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_BAD_IMPLEMENTATION,
                'You must configure your consumer, use "configure" method.'
            )
        );
    }

    /**
     * @return string
     * @throws Throwable
     */
    protected function buildConsumerTag(): string
    {
        $this->validateConfiguration();

        $uniqueHash = substr(md5(BaseConsumer . phptime() . rand(0, 9999)), 0, 10);

        return "{$this->consumerSettings->consumerTagPrefix}_{$uniqueHash}";
    }

    /**
     * @param ConsumerDeclareConfigurator $consumerDeclareConfigurator
     * @return void
     */
    protected function configure(ConsumerDeclareConfigurator $consumerDeclareConfigurator): void
    {
        $this->consumerSettings = $consumerDeclareConfigurator;
    }

    public function __destruct()
    {
        $this->queueInstance = null;
        $this->consumerInstance = null;
        $this->logger = null;
    }
}
