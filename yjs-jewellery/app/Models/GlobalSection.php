<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSection extends Model
{
    protected $table = 'global_sections';

    protected $fillable = [
        'type',
        'name',
        'blocks',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'blocks' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHeader($query)
    {
        return $query->where('type', 'header');
    }

    public function scopeFooter($query)
    {
        return $query->where('type', 'footer');
    }

    // ============ STATIC HELPERS ============

    public static function getActiveHeader(): ?self
    {
        return static::header()->active()->first();
    }

    public static function getActiveFooter(): ?self
    {
        return static::footer()->active()->first();
    }
}
