<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
class Category extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'parent_id',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Spatie Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Category')
            ->logAll()                  // log all fields
            ->logOnlyDirty()            // only log changed values
            ->dontSubmitEmptyLogs()     // avoid empty activity logs
            ->setDescriptionForEvent(
                fn(string $eventName) =>
                    "Category has been {$eventName}"
            );
    }

    public static function getCategoryInfo($categoryId)
    {
        $category = Category::query()
            ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
            ->select(
                'categories.name as Name',
                'categories.description as Description',
                'parent.name as ParentName',
                'categories.created_at as CreatedAt',
                'categories.updated_at as UpdatedAt'
            )
            ->where('categories.id', $categoryId)
            ->first();

        if (!$category) {
            return null;
        }

        $attributes = $category->getAttributes();

        foreach ($attributes as $key => $value) {
            if (is_null($value)) {
                unset($attributes[$key]);
            }
        }

        return (object) $attributes;
    }


    public function products()
    {
        return $this->hasMany(Product::class, 'sub_category_id', 'id')
                    ->where('products.status', 'active')        // optional: only active products
                    ->whereNull('products.deleted_at');         // optional: exclude deleted
    }

    // Optional: If you want parent-child relationship
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing('parent');

        $properties = $activity->properties->toArray();

        // New values
        if (isset($properties['attributes'])) {
            $properties['attributes'] = $this->replaceParent(
                $properties['attributes']
            );
        }

        // Old values
        if (isset($properties['old'])) {
            $properties['old'] = $this->replaceParent(
                $properties['old']
            );
        }

        $activity->properties = $properties;
    }

    /**
     * 🔹 Helper: parent_id → parent name
     */
    protected function replaceParent(array $data): array
    {
        if (isset($data['parent_id'])) {
            $data['parent'] = $this->parent?->name;
            unset($data['parent_id']);
        }

        return $data;
    }

}
