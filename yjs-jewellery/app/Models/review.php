<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
class review extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'rating',
        'comment',
        'status',
    ];

    /**
     * Spatie activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('review')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Review has been {$event}");
    }
}
