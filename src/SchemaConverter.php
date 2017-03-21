<?php

namespace Towa\Converter;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Towa\Converter\Exceptions\InvalidConfiguration;

class SchemaConverter
{
    /** @var array */
    protected $config;

    /**
     * Use the given configuration.
     *
     * @param array $config
     *
     * @return $this
     */
    public function useConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Convert the given model to the specified schema.
     *
     * @param $model
     *
     * @return mixed
     */
    public function convert($model)
    {
        $this->explodeAttributes($this->config['attributes']);
        $schema = $this->getSchema($this->config['schema'], $model);

        foreach ($this->config['attributes'] as $key => $attribute) {
            $value = is_array($attribute)
                ? $this->convertArrayToSchema($model, $attribute)
                : $model->{$attribute};

            $schema = $this->setSchemaProperties($schema, $value, $key);
        }

        return $schema;
    }

    /**
     * Get the given schema class.
     *
     * @param string $schema
     * @param        $model
     *
     * @throws InvalidConfiguration
     *
     * @return mixed
     */
    protected function getSchema(string $schema, $model)
    {
        $schemaClass = 'Spatie\\SchemaOrg\\'.ucfirst($schema);

        if (! class_exists($schemaClass, true)) {
            throw InvalidConfiguration::typeIsNotValid(get_class($model));
        }

        return new $schemaClass();
    }

    /**
     * Converts sub schemas and model relations from the configuration to arrays.
     *
     * @param array $attributes
     */
    protected function explodeAttributes(array $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if (Str::contains($attribute, 'relation')) {
                $this->handleRelationAttribute($key, $attribute);
            }
        }
    }

    /**
     * Converts a given relationship to an array.
     *
     * @param $key
     * @param $attribute
     */
    protected function handleRelationAttribute($key, $attribute)
    {
        // Get only the relation and the requested field
        $relationAndField = explode(':', $attribute);
        // Append a optional "," at the end to prevent an undefined offset of the returned array
        [$relation, $field] = explode(',', $relationAndField[1].',');

        $this->config['attributes'][$key] = [
            'relation' => $relation,
            'field' => $field ?? null,
        ];
    }

    /**
     * Converts a given array (relationship) to the specific schema.
     *
     * @param       $model
     * @param array $attribute
     *
     * @throws InvalidConfiguration
     *
     * @return null
     */
    protected function convertArrayToSchema($model, array $attribute)
    {
        if (array_key_exists('relation', $attribute)) {
            return $this->handleRelation($model, $attribute);
        }

        throw InvalidConfiguration::configIsNotValid(get_class($model));
    }

    /**
     * Convert the given model to the according schema array.
     *
     * @param $model
     *
     * @throws InvalidConfiguration
     *
     * @return mixed
     */
    protected function convertModel($model)
    {
        try {
            $schema = $model->convertToSchema();
        } catch (Exception $e) {
            throw InvalidConfiguration::notExtendingTrait(get_class($model));
        }

        return $schema;
    }

    /**
     * Get the related model and convert it.
     *
     * @param $model
     * @param $attribute
     *
     * @return mixed
     */
    protected function handleRelation($model, $attribute)
    {
        $model = $this->getRelatedModel($model, $attribute);

        if ($model && ! empty($attribute['field'])) {
            return $this->getRequestedFieldFromModel($model, $attribute['field']);
        }

        return $model ? $this->convertModel($model) : null;
    }

    /**
     * Set the schema properties.
     *
     * @param $schema
     * @param $value
     * @param $key
     *
     * @throws InvalidConfiguration
     *
     * @return mixed
     */
    protected function setSchemaProperties($schema, $value, $key)
    {
        if (! method_exists(get_class($schema), $key)) {
            throw InvalidConfiguration::invalidArgument(get_class($schema), $key);
        }

        if (is_array($value) && ! empty($value)) {
            $schema->{$key}($this->parseArrayAttribute($value));
        } else {
            $schema->{$key}($this->parseAttribute($value));
        }

        return $schema;
    }

    /**
     * Get the related model according to the given attribute relation.
     *
     * @param $model
     * @param $attribute
     *
     * @return mixed
     */
    protected function getRelatedModel($model, $attribute)
    {
        $relations = $attribute['relation'];

        if (Str::contains($relations, '.')) {
            $relations = explode('.', $relations);

            foreach ($relations as $relation) {
                $model = $model->{$relation};
            }

            return $model;
        }

        return $model->{$relations};
    }

    /**
     * Retrieve the field defined in the config.
     *
     * @param $model
     * @param $field
     *
     * @return mixed
     */
    protected function getRequestedFieldFromModel($model, $field)
    {
        if ($model instanceof Collection) {
            return $model->map->{$field}->toArray();
        }

        return $model->{$field};
    }

    /**
     * Return the parsed array attribute.
     * Parse all single attributes if the array size is greater than one.
     *
     * @param $array
     *
     * @return array|mixed
     */
    protected function parseArrayAttribute($array)
    {
        $array = collect($array);

        if ($array->count() === 1) {
            return $array[0];
        }

        return $array->map(function ($value) {
            return $this->parseAttribute($value);
        })->toArray();
    }

    /**
     * Parse a single attribute.
     *
     * @param $value
     *
     * @return string
     */
    protected function parseAttribute($value)
    {
        return $value instanceof Carbon
            ? $value->toAtomString()
            : $value;
    }
}
