<?php

namespace Package\PhpUtils\Functionalities;

use Illuminate\Support\Str;
use Package\PhpUtils\Enumerators\ErrorEnum;
use Package\PhpUtils\Exceptions\BadImplementationException;

/**
 * Trait ValuesExporterToSnakeFunctionality
 * @package Package\PhpUtils\Functionalities
 */
trait ValuesExporterToSnakeFunctionality
{
    /**
     * @return array
     * @throws BadImplementationException
     */
    public function toSnakeCase(): array
    {
        if (!method_exists($this, 'properties')) {
            throw new BadImplementationException(
                ErrorEnum::PHU001,
                'You must use the Trait PropertiesExporterFunctionality to use this functionality.'
            );
        }
        $response = [];

        foreach ($this->properties() as $propertyClass) {
            $response[Str::snake($propertyClass)] = $this->{$propertyClass};
        }

        return $response;
    }
}
