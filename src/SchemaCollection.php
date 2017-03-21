<?php

namespace Towa\Converter;

use Illuminate\Database\Eloquent\Collection;

class SchemaCollection extends Collection
{
    public function toScript()
    {
        return '<script type="application/ld+json">' . json_encode($this) . '</script>';
    }
}
