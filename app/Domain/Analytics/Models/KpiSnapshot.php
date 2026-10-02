<?php

namespace App\Domain\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSnapshot extends Model
{
    protected $fillable = ['kpi_id', 'value', 'target_value', 'achieved', 'captured_on'];

    protected function casts(): array
    {
        return ['value' => 'float', 'target_value' => 'float', 'achieved' => 'boolean', 'captured_on' => 'date'];
    }
}
