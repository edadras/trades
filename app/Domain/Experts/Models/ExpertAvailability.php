<?php

namespace App\Domain\Experts\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertAvailability extends Model
{
    protected $table = 'expert_availability';

    protected $fillable = ['expert_profile_id', 'weekday', 'starts_at', 'ends_at'];
}
