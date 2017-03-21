<?php

namespace Towa\Converter\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Towa\Converter\Traits\SchemaConvertible;

class Event extends Model
{
    use SchemaConvertible;

    protected $table = 'events';
    protected $guarded = [];
    protected $dates = ['start_date', 'end_date'];

    protected static $convertToSchema = [
        'schema' => 'Event',
        'attributes' => [
            'name' => 'name',
            'description' => 'description',
            'startDate' => 'relation:dates,start_date',
            'endDate' => 'relation:dates,end_date',
            'location' => 'relation:location',
        ],
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function dates()
    {
        return $this->hasMany(EventDate::class);
    }
}
