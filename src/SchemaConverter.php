<?php

namespace Towa\Converter;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Towa\Converter\Exceptions\InvalidConfiguration;

class SchemaConverter
{
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
     * Get the given schema type.
     *
     * @param string $schema
     * @param        $model
     *
     * @return mixed
     * @throws InvalidConfiguration
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
        // Get only the relation and it's attribute
        $exploded = explode(':', $attribute);
        $relationAndAttribute = explode(',', $exploded[1]);

        $this->config['attributes'][$key] = [
            'relation' => $relationAndAttribute[0],
        ];
    }

    /**
     * Converts a given array (relationship) to the specific schema.
     *
     * @param       $model
     * @param array $attribute
     *
     * @return null
     * @throws InvalidConfiguration
     */
    protected function convertArrayToSchema($model, array $attribute)
    {
        if (array_key_exists('relation', $attribute)) {
            return $this->handleRelation($model, $attribute);
        }

        throw InvalidConfiguration::configIsNotValid();
    }

    protected function convertModel($model)
    {
        try {
            $schema = $model->convertToSchema();
        } catch (Exception $e) {
            throw InvalidConfiguration::notExtendingTrait(get_class($model));
        }

        return $schema;
    }

    protected function handleRelation($model, $attribute)
    {
        $model = $this->getRelatedModel($model, $attribute);

        return $this->convertModel($model);
    }

    protected function setSchemaProperties($schema, $value, $key)
    {
        if (! method_exists(get_class($schema), $key)) {
            throw InvalidConfiguration::invalidArgument(get_class($schema), $key);
        }

        if (is_array($value) && ! empty($value)) {
            $schema = $schema->{$key}(count($value) > 1 ? $value : $value[0]);
        } else {
            $schema->{$key}(
                $value instanceof Carbon
                    ? $value->toAtomString()
                    : $value
            );
        }

        return $schema;
    }

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
}
