<?php

namespace Package\Queues\Configurators;

use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\PhpUtils\Functionalities\PropertiesExporterFunctionality;
use Package\Queues\Enums\ExchangeTypeEnum;

/**
 * Class ExchangeDeclareConfigurator
 * @package Package\Queues\Configurators
 */
class ExchangeDeclareConfigurator
{
    use PropertiesExporterFunctionality;
    use PropertiesAttacherFunctionality;

    /**
     * @required
     * @var string
     */
    public $name;
    /**
     * @required
     * @var string
     * @see ExchangeTypeEnum
     */
    public $type;
    /**
     * @var bool
     */
    public $passive = false;
    /**
     * @var bool
     */
    public $durable = false;
    /**
     * @var bool
     */
    public $autoDelete = true;
    /**
     * @var bool
     */
    public $internal = false;
    /**
     * @var bool
     */
    public $nowait = false;
    /**
     * @var array
     */
    public $arguments = [];
    /**
     * @var null
     */
    public $ticket = null;
}
