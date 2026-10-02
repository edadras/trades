<?php

namespace App\Domain\Business\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Partner organisation that refers businesses/cases (chamber, association, NGO, …). */
class Partner extends Model
{
    use HasTranslations;

    public const TYPES = ['chamber', 'association', 'ngo', 'government', 'university', 'accelerator', 'other'];

    protected array $translatable = ['name'];

    protected $fillable = ['name', 'type', 'referral_code', 'contact_name', 'contact_email', 'referral_method', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $p) => $p->referral_code ??= Str::upper(Str::random(8)));
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }
}
