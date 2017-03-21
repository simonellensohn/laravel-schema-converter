<?php

namespace Towa\Converter\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Towa\Converter\Traits\SchemaConvertible;

class Address extends Model
{
    use SchemaConvertible;

    protected $table = 'addresses';
    protected $guarded = [];

    protected static $convertToSchema = [
        'schema' => 'PostalAddress',
        'attributes' => [
            'streetAddress' => 'street',
            'addressLocality' => 'city',
            'addressRegion' => 'region',
            'postalCode' => 'zipcode',
            'addressCountry' => 'country_code',
        ],
    ];
}