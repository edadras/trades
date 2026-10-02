<?php

namespace App\Domain\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    protected $fillable = ['kpi_id', 'target_value', 'period_start', 'period_end', 'created_by'];

    protected function casts(): array
    {
        return ['target_value' => 'float', 'period_start' => 'date', 'period_end' => 'date'];
    }
}
