<?php

namespace Package\PhpUtils\Functionalities;

use Package\PhpUtils\Enumerators\ErrorEnum;
use Package\PhpUtils\Exceptions\BadImplementationException;

/**
 * Trait PropertiesAttacherFunctionality
 * @package Package\PhpUtils\Functionalities
 */
trait PropertiesAttacherFunctionality
{
    /**
     * @param array $values
     * @throws BadImplementationException
     */
    public function attachValues(array $values): void
    {
        if (!method_exists($this, 'properties')) {
            throw new BadImplementationException(
                ErrorEnum::PHU001,
                'You must use the Trait PropertiesExporterFunctionality to use this functionality.'
            );
        }

        $properties = $this->properties();
        foreach ($values as $propertyName => $propertyValue) {
            if (!in_array($propertyName, $properties)) {
                continue;
            }

            $this->{$propertyName} = $propertyValue;
        }
    }
}
