<?php

namespace App\Domain\Experts\Models;

use App\Support\SecureFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpertDocument extends Model
{
    use SecureFile, SoftDeletes;

    protected $fillable = [
        'expert_profile_id', 'uploaded_by', 'type', 'title', 'disk', 'path', 'original_name', 'mime_type', 'size',
        'checksum', 'scan_status',
    ];

    public static function fileType(): string
    {
        return 'expert';
    }

    public function expertProfile(): BelongsTo
    {
        return $this->belongsTo(ExpertProfile::class);
    }
}
