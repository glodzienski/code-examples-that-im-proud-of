<?php

namespace Package\Queues\Example;

use Package\PhpUtils\Dto\Dto;
use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\PhpUtils\Functionalities\PropertiesExporterFunctionality;
use Package\Queues\Core\BaseDto;

/**
 * Class ExamplePayloadDto
 * @package Package\Queues\Example
 */
class ExamplePayloadDto extends BaseDto
{
    use PropertiesExporterFunctionality;
    use PropertiesAttacherFunctionality;

    /**
     * @var string
     */
    public $test = 'test';
}
