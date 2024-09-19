<?php

namespace Package\Queues\Core;

/**
 * Class BaseConsumerConfigurator
 * @package Package\Queues\Core
 */
abstract class BaseConsumerConfigurator
{
    /**
     * @var string
     */
    public $consumerClass;
    /**
     * @var string
     */
    public $consumerHandlerClass;
    /**
     * @var int
     */
    public $quantity = 1;
    /**
     * Valor em minutos
     * @var int
     */
    public $lifetime = 240;
}
