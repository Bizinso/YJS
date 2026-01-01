<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GemstoneRateMaster extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'gemstone_rate_masters';

    protected $fillable = [
        'gemstone_type_id',
        'rate_per_carat',
        'effective_date_from',
        'effective_date_to',
        'stone_shape_id',
        'stone_color_id',
        'stone_clarity_id',
        'stone_cut_id',
        'size_id',
        'purity_id',
        'combination_keys',
        'treatment',
        'status',
    ];

    protected $casts = [
        'effective_date_from' => 'date',
        'effective_date_to' => 'date',
        'rate_per_carat' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('GemstoneRateMaster')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Gemstone Rate Master has been {$event}"
            );
    }
}
