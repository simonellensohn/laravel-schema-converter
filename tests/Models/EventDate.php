<?php

namespace Towa\Converter\Test\Models;

use Illuminate\Database\Eloquent\Model;

class EventDate extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'event_id',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'deleted_at',
    ];
}
