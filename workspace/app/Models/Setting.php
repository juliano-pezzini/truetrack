<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'validation_rules',
        'min_value',
        'max_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validation_rules' => 'array',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
    ];

    /**
     * Get the change history for this setting.
     */
    public function changes(): HasMany
    {
        return $this->hasMany(SettingChange::class, 'setting_key', 'key');
    }

    /**
     * Get a setting value by key with caching.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value with validation and audit trail.
     *
     * @throws \InvalidArgumentException
     */
    public static function setValue(string $key, mixed $value, int $userId): void
    {
        $setting = self::where('key', $key)->firstOrFail();

        // Validate the new value
        if (! $setting->validate($value)) {
            throw new \InvalidArgumentException("Invalid value for setting: {$key}");
        }

        $oldValue = $setting->value;
        $setting->value = (string) $value;
        $setting->save();

        // Record the change
        $setting->recordChange($oldValue, (string) $value, $userId);

        // Clear cache
        Cache::forget("setting.{$key}");
    }

    /**
     * Validate a value against the setting's constraints.
     */
    public function validate(mixed $value): bool
    {
        // Type validation
        $castedValue = self::castValue($value, $this->type);
        if ($castedValue === null && $value !== null) {
            return false;
        }

        // Min/max validation for numeric types
        if (in_array($this->type, ['integer', 'float']) && $castedValue !== null) {
            if ($this->min_value !== null && $castedValue < $this->min_value) {
                return false;
            }
            if ($this->max_value !== null && $castedValue > $this->max_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Record a change in the audit trail.
     */
    public function recordChange(?string $oldValue, string $newValue, int $userId): void
    {
        SettingChange::create([
            'setting_key' => $this->key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by_user_id' => $userId,
            'changed_at' => now(),
        ]);
    }

    /**
     * Cast a value to the specified type.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => is_numeric($value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            default => $value,
        };
    }
}
