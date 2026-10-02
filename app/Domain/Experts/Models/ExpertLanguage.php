<?php

namespace App\Domain\Experts\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertLanguage extends Model
{
    protected $fillable = ['expert_profile_id', 'language', 'proficiency'];
}
