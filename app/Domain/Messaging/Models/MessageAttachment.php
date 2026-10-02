<?php

namespace App\Domain\Messaging\Models;

use App\Support\SecureFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use SecureFile;

    protected $fillable = ['message_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'checksum', 'scan_status', 'duration_seconds'];

    public static function fileType(): string
    {
        return 'message';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
