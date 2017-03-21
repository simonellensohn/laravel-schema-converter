<?php

namespace Towa\Converter\Traits;

use Illuminate\Support\Collection;
use Towa\Converter\SchemaConverter;
use Towa\Converter\Exceptions\InvalidConfiguration;

trait SchemaConvertible
{
    protected static $config;

    protected static $requiredConfigKeys = [
        'schema',
        'attributes',
    ];

    protected static function bootSchemaConvertible()
    {
        static::$config = static::columnsToBeConverted();

        static::validateConfiguration(static::$config);
    }

    public function convertToSchema()
    {
        return app(SchemaConverter::class)
            ->useConfig(static::$config->toArray())
            ->convert($this);
    }

    /*
     * Get the columns that should be recorded converted.
     */
    protected static function columnsToBeConverted()
    {
        if (isset(static::$convertToSchema)) {
            return collect(static::$convertToSchema);
        }

        throw InvalidConfiguration::modelIsNotValid(self::class);
    }

    protected static function validateConfiguration(Collection $config)
    {
        $validConfig = collect(static::$requiredConfigKeys)
            ->values()
            ->diff($config->keys())
            ->isEmpty();

        if ($validConfig) {
            return;
        }

        throw InvalidConfiguration::configIsNotValid(self::class);
    }
}
