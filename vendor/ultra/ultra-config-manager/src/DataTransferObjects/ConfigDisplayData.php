<?php

/**
 * 📜 Oracode DTO: ConfigDisplayData
 *
 * @package         Ultra\UltraConfigManager\DataTransferObjects
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\DataTransferObjects;

use Ultra\UltraConfigManager\Enums\CategoryEnum; // Import Enum

/**
 * 🎯 Purpose: Data Transfer Object representing the essential information of a
 *    configuration entry needed for display in listings (like the index view).
 *    Provides a structured and type-safe way to pass data from the Manager/DAO
 *    to the presentation layer.
 *
 * 🧱 Structure: Readonly class with public properties for core config attributes.
 *
 * 🛡️ Privacy: Contains potentially sensitive `key`. `value` should ideally be
 *    truncated, masked, or represented as a type indicator if sensitive.
 *
 * @package Ultra\UltraConfigManager\DataTransferObjects
 */
final readonly class ConfigDisplayData
{
    /**
     * @param int $id The unique identifier of the configuration.
     * @param string $key The unique configuration key.
     * @param string $displayValue A representation of the value suitable for display (e.g., truncated, masked, type indicator).
     * @param ?string $categoryValue The raw string value of the category (from Enum).
     * @param ?string $categoryLabel The translated label for the category.
     * @param ?string $note Optional note associated with the config.
     * @param ?\Illuminate\Support\Carbon $updatedAt Timestamp of the last update.
     */
    public function __construct(
        public int $id,
        public string $key,
        public string $displayValue, // Value formatted for safe display
        public ?string $categoryValue,
        public ?string $categoryLabel,
        public ?string $note,
        public ?\Illuminate\Support\Carbon $updatedAt
    ) {}

    /**
     * 🏭 Static factory method to create DTO from a Model instance.
     * Handles value formatting and category translation.
     *
     * @param \Ultra\UltraConfigManager\Models\UltraConfigModel $model
     * @param int $valueMaxLength Max length for display value before truncation.
     * @return self
     */
    public static function fromModel(\Ultra\UltraConfigManager\Models\UltraConfigModel $model, int $valueMaxLength = 50): self
    {
        $rawValue = $model->value; // Assumes value is already decrypted by model cast
        $displayValue = match (true) {
            is_null($rawValue) => '[NULL]',
            is_bool($rawValue) => $rawValue ? 'true' : 'false',
            is_array($rawValue) => '[Array]',
            is_object($rawValue) => '[' . class_basename($rawValue) . ']',
            is_string($rawValue) && mb_strlen($rawValue) > $valueMaxLength => mb_substr($rawValue, 0, $valueMaxLength) . '...',
            default => (string) $rawValue,
        };

        return new self(
            id: $model->id,
            key: $model->key,
            displayValue: $displayValue,
            categoryValue: $model->category?->value,
            categoryLabel: $model->category?->translatedName() ?? __('uconfig::uconfig.categories.none'),
            note: $model->note,
            updatedAt: $model->updated_at
        );
    }
}