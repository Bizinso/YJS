<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tax Zone Model
 *
 * Geographical regions with specific tax rules.
 */
class TaxZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'countries',
        'states',
        'pincodes',
        'is_default',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'countries' => 'array',
        'states' => 'array',
        'pincodes' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    /**
     * Find matching zone for a given address.
     */
    public static function findForAddress(string $country, ?string $state = null, ?string $pincode = null): ?self
    {
        $query = self::where('is_active', true)
            ->orderByDesc('priority');

        // Try to find exact match
        $zones = $query->get();

        foreach ($zones as $zone) {
            // Check pincode first (most specific)
            if ($pincode && $zone->pincodes) {
                foreach ($zone->pincodes as $pattern) {
                    if (self::matchesPincode($pincode, $pattern)) {
                        return $zone;
                    }
                }
            }

            // Check state
            if ($state && $zone->states && in_array($state, $zone->states)) {
                return $zone;
            }

            // Check country
            if ($zone->countries && in_array($country, $zone->countries)) {
                return $zone;
            }
        }

        // Return default zone if no match
        return self::where('is_default', true)->first();
    }

    /**
     * Check if pincode matches a pattern.
     */
    protected static function matchesPincode(string $pincode, string $pattern): bool
    {
        // Support wildcards like "40*" or "400-410"
        if (str_contains($pattern, '*')) {
            $prefix = str_replace('*', '', $pattern);
            return str_starts_with($pincode, $prefix);
        }

        if (str_contains($pattern, '-')) {
            [$start, $end] = explode('-', $pattern);
            return $pincode >= $start && $pincode <= $end;
        }

        return $pincode === $pattern;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
