<?php

namespace Package\Queues\Core;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Package\PhpUtils\Functionalities\PropertiesAttacherFunctionality;
use Package\PhpUtils\Functionalities\PropertiesExporterFunctionality;
use Package\PhpUtils\Functionalities\ValuesExporterToArrayFunctionality;
use Package\PhpUtils\Functionalities\ValuesExporterToJsonFunctionality;
use Package\PhpUtils\Functionalities\ValuesExporterToSnakeFunctionality;

/**
 * Class BaseDto
 */
abstract class BaseDto implements Arrayable, Jsonable
{
    use PropertiesExporterFunctionality;
    use PropertiesAttacherFunctionality;
    use ValuesExporterToArrayFunctionality;
    use ValuesExporterToJsonFunctionality;
    use ValuesExporterToSnakeFunctionality;
}
