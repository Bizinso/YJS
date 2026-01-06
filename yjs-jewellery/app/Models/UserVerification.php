<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Verification Model
 *
 * Tracks user verification status for various checks.
 */
class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'data',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'data' => 'array',
        'verified_at' => 'datetime',
    ];

    // Verification Types
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';
    const TYPE_DOCUMENT = 'document';
    const TYPE_KYC = 'kyc';
    const TYPE_ADDRESS = 'address';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verify the verification request.
     */
    public function verify(?int $verifiedBy = null): self
    {
        $this->status = self::STATUS_VERIFIED;
        $this->verified_by = $verifiedBy ?? auth()->id();
        $this->verified_at = now();
        $this->save();
        return $this;
    }

    /**
     * Reject the verification request.
     */
    public function reject(string $reason, ?int $verifiedBy = null): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->verified_by = $verifiedBy ?? auth()->id();
        $this->verified_at = now();
        $this->save();
        return $this;
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
