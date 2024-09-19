<?php

namespace Package\Queues\Core;

use Package\PhpUtils\Dto\Dto;
use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\Queues\Configurators\ExchangeDeclareConfigurator;
use Package\Queues\Enums\ExceptionEnum;
use Package\PhpUtils\Exceptions\BadImplementationException;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class BaseExchange
 * @package Package\Queues\Core
 */
abstract class BaseExchange
{
    /**
     * @var ExchangeDeclareConfigurator
     */
    private $exchangeSettings;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     * @required
     */
    protected $dtoPayloadClass;

    /**
     * @var string
     * default value is BAD_IMPLEMENTATION to get in logs who bad implemented
     * @required
     */
    protected $logTokenPrefix = 'BAD_IMPLEMENTATION';

    /**
     * BaseExchange constructor.
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     *
     * @throws BadImplementationException
     */
    public function create(): void
    {
        $this->validateConfiguration();

        $this->channel()
            ->exchange_declare(
                $this->exchangeSettings->name,
                $this->exchangeSettings->type,
                $this->exchangeSettings->passive,
                $this->exchangeSettings->durable,
                $this->exchangeSettings->autoDelete,
                $this->exchangeSettings->internal,
                $this->exchangeSettings->nowait,
                $this->exchangeSettings->arguments,
                $this->exchangeSettings->ticket
            );
    }

    /**
     * @return void
     * @throws BadImplementationException
     */
    private function validateConfiguration(): void
    {
        $classSettingsNotExists = is_null($this->exchangeSettings);
        $classSettingsRequiredPropsNotConfigured = empty($this->exchangeSettings->name)
            || empty($this->exchangeSettings->type);
        throw_if(
            $classSettingsNotExists || $classSettingsRequiredPropsNotConfigured,
            new BadImplementationException(
                ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                'You must configure your exchange, use "configure" method.'
            )
        );

        throw_if(
            is_null($this->dtoPayloadClass),
            new BadImplementationException(
                ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                'You must configure your DTO Payload Class.'
            )
        );

        $dtoPayloadInstance = new $this->dtoPayloadClass();

        $dtoPayloadClassDoesNotInstanceOfTypeDto = !($dtoPayloadInstance instanceof Dto);
        throw_if(
            $dtoPayloadClassDoesNotInstanceOfTypeDto,
            new BadImplementationException(
                ExceptionEnum::EXCHANGE_BAD_IMPLEMENTATION,
                'Your DTO Payload Class does not instance of DTO type.'
            )
        );

        $firstParameterDoesNotUseAttacher = !(
            in_array(PropertiesAttacherFunctionality::class, class_uses($dtoPayloadInstance))
        );
        throw_if(
            $firstParameterDoesNotUseAttacher,
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'Your DTO Payload Class does not uses Attacher Functionality.'
            )
        );
    }

    /**
     * @param object $object
     * @throws BadImplementationException
     */
    public function validateIfObjectInstanceOfDtoPayloadClass(object $object): void
    {
        throw_if(
            !($object instanceof $this->dtoPayloadClass),
            new BadImplementationException(
                ExceptionEnum::CONSUMER_HANDLER_BAD_IMPLEMENTATION,
                'The first parameter of handle method, must be of Queue DtoPayload Configured.'
            )
        );
    }

    /**
     * @return AMQPChannel
     */
    public function channel(): AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @return string
     * @throws BadImplementationException
     */
    public function name(): string
    {
        $this->validateConfiguration();

        return $this->exchangeSettings->name;
    }

    /**
     * @param ExchangeDeclareConfigurator $exchangeDeclareParametersDto
     * @return void
     */
    protected function configure(ExchangeDeclareConfigurator $exchangeDeclareParametersDto): void
    {
        $this->exchangeSettings = $exchangeDeclareParametersDto;
    }

    /**
     * @return string
     * @throws BadImplementationException
     */
    public function getDtoPayloadClass(): string
    {
        $this->validateConfiguration();

        return $this->dtoPayloadClass;
    }

    /**
     * @return string
     */
    public function getLogTokenPrefix(): string
    {
        return $this->logTokenPrefix;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        if (is_null($this->channel)) {
            return;
        }

        $this->channel->close();
        $this->channel = null;
    }
}
