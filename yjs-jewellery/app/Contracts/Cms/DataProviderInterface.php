<?php

namespace App\Contracts\Cms;

/**
 * DataProviderInterface - Contract for Dynamic Data Sources
 *
 * Implement this interface to create pluggable data providers
 * that widgets can use for dynamic content.
 */
interface DataProviderInterface
{
    /**
     * Get unique identifier for this provider.
     */
    public function getIdentifier(): string;

    /**
     * Get display name for UI.
     */
    public function getName(): string;

    /**
     * Get description for UI.
     */
    public function getDescription(): string;

    /**
     * Get configuration schema for UI.
     * Returns field definitions that the admin can configure.
     *
     * Example:
     * [
     *     'source' => [
     *         'type' => 'select',
     *         'label' => 'Source',
     *         'options' => ['featured', 'category', 'manual'],
     *         'default' => 'featured'
     *     ],
     *     'limit' => [
     *         'type' => 'number',
     *         'label' => 'Limit',
     *         'default' => 8,
     *         'min' => 1,
     *         'max' => 50
     *     ]
     * ]
     */
    public function getConfigSchema(): array;

    /**
     * Get available fields for data mapping.
     * Returns fields that can be bound to widget properties.
     *
     * Example:
     * [
     *     'id' => ['type' => 'integer', 'label' => 'ID'],
     *     'name' => ['type' => 'string', 'label' => 'Name'],
     *     'price' => ['type' => 'money', 'label' => 'Price'],
     *     'image' => ['type' => 'image', 'label' => 'Image URL']
     * ]
     */
    public function getAvailableFields(): array;

    /**
     * Fetch data based on configuration.
     * Returns a DataResult with items and metadata.
     */
    public function fetch(array $config): DataResult;

    /**
     * Fetch a single item by ID.
     */
    public function fetchById(int|string $id): ?array;

    /**
     * Check if provider supports a capability.
     * Capabilities: 'pagination', 'search', 'filter', 'sort'
     */
    public function supports(string $capability): bool;

    /**
     * Get cache TTL in seconds.
     */
    public function getCacheTtl(): int;

    /**
     * Generate cache key for a config.
     */
    public function getCacheKey(array $config): string;
}
