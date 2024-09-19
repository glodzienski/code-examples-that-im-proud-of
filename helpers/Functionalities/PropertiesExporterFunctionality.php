<?php

namespace Package\PhpUtils\Functionalities;

/**
 * Trait PropertiesExporterFunctionality
 * @package Package\PhpUtils\Functionalities
 */
trait PropertiesExporterFunctionality
{
    /**
     * @return array
     */
    public static function properties(): array
    {
        return array_keys(get_class_vars(self::class));
    }
}
