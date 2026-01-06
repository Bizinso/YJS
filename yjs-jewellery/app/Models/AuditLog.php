<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit Log Model
 *
 * Enhanced system audit logging.
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // Event Types
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_EXPORT = 'export';
    const EVENT_IMPORT = 'import';
    const EVENT_VIEW = 'view';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_STATUS_CHANGE = 'status_change';
    const EVENT_PERMISSION_CHANGE = 'permission_change';
    const EVENT_SETTINGS_CHANGE = 'settings_change';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an audit event.
     */
    public static function log(
        string $event,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        array $metadata = []
    ): self {
        return self::create([
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'user_id' => auth()->id(),
            'user_type' => auth()->user()?->user_type,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get changes summary.
     */
    public function getChangesSummary(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'from' => $oldValue,
                        'to' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Scopes
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, string $type, ?int $id = null)
    {
        $query->where('auditable_type', $type);
        if ($id) {
            $query->where('auditable_id', $id);
        }
        return $query;
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
}
