<?php

namespace App\Domain\Compliance\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * A type of cross-party service (education, mentoring, investment, fund transfer, …) and whether
 * it is allowed, needs legal/security review before activation, or is disabled.
 */
class ServicePath extends Model
{
    use HasTranslations;

    public const MODES = ['allowed', 'review_required', 'disabled'];

    protected array $translatable = ['name', 'description'];

    protected $fillable = ['key', 'name', 'description', 'mode', 'required_documents', 'sort_order'];

    protected function casts(): array
    {
        return ['required_documents' => 'array'];
    }

    public function toOption(): array
    {
        return ['id' => $this->id, 'key' => $this->key, 'name' => $this->translate('name'), 'description' => $this->translate('description'), 'mode' => $this->mode, 'required_documents' => $this->required_documents ?? []];
    }
}
