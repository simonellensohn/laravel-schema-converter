<?php

namespace Towa\Converter\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("{$className} does not contain any valid converter configuration.");
    }

    public static function configIsNotValid(string $className)
    {
        return new static("The given Converter config for {$className} is invalid.");
    }

    public static function typeIsNotValid(string $className)
    {
        return new static("The given schema type for {$className} is invalid.");
    }

    public static function configMissing(string $className)
    {
        return new static("The schema config for {$className} is missing.");
    }

    public static function notExtendingTrait(string $className)
    {
        return new static("{$className} is not extending the Schema trait.");
    }

    public static function invalidArgument(string $className, string $argument)
    {
        return new static("{$argument} is not a valid argument for {$className}");
    }
}
