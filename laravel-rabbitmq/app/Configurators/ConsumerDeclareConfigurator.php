<?php

namespace Package\Queues\Configurators;

use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\PhpUtils\Functionalities\PropertiesExporterFunctionality;

/**
 * Class ConsumerDeclareConfigurator
 * @package Package\Queues\Configurators
 */
class ConsumerDeclareConfigurator extends BaseConsumerConfigurator
{
    use PropertiesExporterFunctionality;
    use PropertiesAttacherFunctionality;

    /**
     * @var string
     */
    public $consumerTagPrefix;
    /**
     * @var bool
     */
    public $noLocal = false;
    /**
     * @var
     */
    public $noAck = false;
    /**
     * @var
     */
    public $exclusive = false;
    /**
     * @var
     */
    public $noWait = false;
    /**
     * @var int
     */
    public $prefetchSize = 10;
}
