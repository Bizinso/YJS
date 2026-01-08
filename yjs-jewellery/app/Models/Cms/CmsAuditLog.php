<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CmsAuditLog Model - Complete Change History
 *
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $action
 * @property array|null $old_values
 * @property array|null $new_values
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property \Carbon\Carbon $created_at
 */
class CmsAuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'cms_audit_log';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============ SCOPES ============

    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('auditable_type', $type)->where('auditable_id', $id);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ============ HELPERS ============

    /**
     * Log an action.
     */
    public static function log(
        string $type,
        int $id,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): self {
        return static::create([
            'auditable_type' => $type,
            'auditable_id' => $id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log page action.
     */
    public static function logPage(Page $page, string $action, ?array $oldValues = null, ?array $newValues = null): self
    {
        return static::log(Page::class, $page->id, $action, $oldValues, $newValues);
    }

    /**
     * Log version action.
     */
    public static function logVersion(PageVersion $version, string $action, ?array $context = null): self
    {
        return static::log(
            PageVersion::class,
            $version->id,
            $action,
            null,
            $context
        );
    }

    /**
     * Get action description.
     */
    public function getActionDescription(): string
    {
        $descriptions = [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'published' => 'Published',
            'unpublished' => 'Unpublished',
            'version_created' => 'New version created',
            'version_published' => 'Version published',
            'version_reverted' => 'Reverted to previous version',
            'scheduled' => 'Scheduled for publish',
            'unscheduled' => 'Schedule removed',
        ];

        return $descriptions[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get changed fields.
     */
    public function getChangedFields(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];

        foreach ($this->new_values as $key => $value) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue !== $value) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                ];
            }
        }

        return $changed;
    }
}
