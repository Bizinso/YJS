<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Partner Model
 *
 * Represents a B2B partner/business account in the system.
 * Partners have special pricing tiers and bulk ordering capabilities.
 *
 * @property int $id
 * @property int $user_id
 * @property string $business_name
 * @property string $business_type
 * @property string|null $phone_number
 * @property string|null $gst_number
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string $status (pending, approved, rejected)
 */
class Partner extends Model
{
    use SoftDeletes, LogsActivity, HasFactory;

    protected $table = 'partners';

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'phone_number',
        'gst_number',
        'address',
        'city',
        'state',
        'status',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Partner')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // log only changed attributes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Partner has been {$event}");
    }
    /**
     * Get the user associated with this partner.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get orders placed by this partner.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'user_id');
    }

    /**
     * Check if partner is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if partner is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if partner is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Approved',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get business type label for display.
     */
    public function getBusinessTypeLabelAttribute(): string
    {
        return match ($this->business_type) {
            'proprietorship' => 'Proprietorship',
            'partnership' => 'Partnership',
            'pvt_ltd' => 'Private Limited',
            'llp' => 'LLP',
            'other' => 'Other',
            default => ucfirst($this->business_type),
        };
    }

    /**
     * Scope for approved partners only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending partners only.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
