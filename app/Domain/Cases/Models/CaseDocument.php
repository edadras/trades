<?php

namespace App\Domain\Cases\Models;

use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseDocument extends Model
{
    use SecureFile, SoftDeletes;

    protected $fillable = [
        'case_id', 'uploaded_by', 'title', 'disk', 'path', 'original_name', 'mime_type', 'size', 'checksum',
        'scan_status', 'is_requested',
    ];

    protected function casts(): array
    {
        return ['is_requested' => 'boolean'];
    }

    public static function fileType(): string
    {
        return 'case';
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
