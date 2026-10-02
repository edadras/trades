<?php

namespace App\Domain\Business\Models;

use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessDocument extends Model
{
    use SecureFile, SoftDeletes;

    protected $fillable = [
        'business_id', 'uploaded_by', 'type', 'title', 'disk', 'path', 'original_name', 'mime_type', 'size',
        'checksum', 'scan_status', 'visibility',
    ];

    public static function fileType(): string
    {
        return 'business';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
