<?php

namespace Package\Queues\Configurators;

use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\PhpUtils\Functionalities\PropertiesExporterFunctionality;

/**
 * Class QueueDeclareConfigurator
 * @package Package\Queues\Configurators
 */
class QueueDeclareConfigurator
{
    use PropertiesExporterFunctionality;
    use PropertiesAttacherFunctionality;

    /**
     * @required
     * @var string
     */
    public $name;
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
    public $exclusive = false;
    /**
     * @var bool
     */
    public $autoDelete = true;
    /**
     * @var bool
     */
    public $nowait = false;
    /**
     * @var array
     */
    public $arguments = [];
    /**
     * @var int
     */
    public $ticket;

    /**
     * @var string
     */
    public $routingKey = '';
}
