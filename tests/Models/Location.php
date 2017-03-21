<?php

namespace Towa\Converter\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Towa\Converter\Traits\SchemaConvertible;

class Location extends Model
{
    use SchemaConvertible;

    protected $table = 'locations';
    protected $guarded = [];

    protected static $convertToSchema = [
        'schema' => 'EventVenue',
        'attributes' => [
            'name' => 'name',
            'description' => 'description',
            'url' => 'url',
            'address' => 'relation:address',
        ],
    ];

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
}
