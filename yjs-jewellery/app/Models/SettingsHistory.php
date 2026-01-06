<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Settings History Model
 *
 * Audit trail for all settings changes.
 */
class SettingsHistory extends Model
{
    use HasFactory;

    protected $table = 'settings_history';

    protected $fillable = [
        'setting_id',
        'group',
        'key',
        'old_value',
        'new_value',
        'changed_by',
    ];

    /**
     * Relationships
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(SystemSetting::class, 'setting_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scopes
     */
    public function scopeForSetting($query, int $settingId)
    {
        return $query->where('setting_id', $settingId);
    }

    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
