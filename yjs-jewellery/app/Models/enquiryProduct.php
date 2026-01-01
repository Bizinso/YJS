<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class enquiryProduct extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'enquiry_products';

    protected $fillable = [
        'enquiry_id',
        'product_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('enquiryProduct')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only when changed
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Enquiry Product has been {$event}"
            );
    }

    // Relationships
    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
