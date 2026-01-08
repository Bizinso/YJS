<?php

namespace App\Models\Cms;

use Database\Factories\Cms\ContentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContentType Model - UI-Defined Page Structures
 *
 * Content types define the schema for pages (like WordPress post types).
 * They're fully configurable from the admin UI.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property array $fields
 * @property array|null $default_widgets
 * @property array|null $default_seo
 * @property bool $supports_versioning
 * @property bool $supports_scheduling
 * @property bool $is_system
 * @property bool $is_active
 */
class ContentType extends Model
{
    use HasFactory;

    protected $table = 'content_types';

    protected static function newFactory()
    {
        return ContentTypeFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'fields',
        'default_widgets',
        'default_seo',
        'supports_versioning',
        'supports_scheduling',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'default_widgets' => 'array',
        'default_seo' => 'array',
        'supports_versioning' => 'boolean',
        'supports_scheduling' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    /**
     * Pages using this content type.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'content_type_id');
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    // ============ HELPERS ============

    /**
     * Get field definitions.
     */
    public function getFields(): array
    {
        return $this->fields ?? [];
    }

    /**
     * Get a specific field definition.
     */
    public function getField(string $key): ?array
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            if (($field['key'] ?? null) === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Validate custom field values against schema.
     */
    public function validateCustomFields(array $values): array
    {
        $errors = [];
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (!$key) continue;

            $value = $values[$key] ?? null;
            $required = $field['required'] ?? false;

            if ($required && empty($value)) {
                $errors[$key] = "The {$field['label']} field is required.";
            }

            // Type validation
            if ($value !== null) {
                $type = $field['type'] ?? 'text';

                switch ($type) {
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$key] = "The {$field['label']} must be a number.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$key] = "The {$field['label']} must be a valid email.";
                        }
                        break;

                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$key] = "The {$field['label']} must be a valid URL.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Get default widgets for new pages.
     */
    public function getDefaultWidgets(): array
    {
        return $this->default_widgets ?? [];
    }

    /**
     * Get default SEO settings for new pages.
     */
    public function getDefaultSeo(): array
    {
        return $this->default_seo ?? [];
    }
}
