<?php

namespace App\Domain\Cases\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAnswer extends Model
{
    protected $fillable = ['case_id', 'question_key', 'question', 'answer', 'source'];

    protected function casts(): array
    {
        return ['answer' => 'encrypted'];
    }
}
