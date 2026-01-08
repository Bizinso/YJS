<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'page_templates';

    protected $fillable = [
        'name',
        'thumbnail',
        'blocks',
        'settings',
        'category',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'blocks' => 'array',
        'settings' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    public function pages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'template_id');
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
