<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
	protected $table = 'platform_settings';
	protected $fillable = ['key', 'value'];

	public static function getString(string $key, ?string $default = null): ?string
	{
		return optional(static::query()->where('key', $key)->first())->value ?? $default;
	}

	public static function getFloat(string $key, ?float $default = null): ?float
	{
		$value = static::getString($key);
		return $value !== null ? (float) $value : $default;
	}

	public static function getInt(string $key, ?int $default = null): ?int
	{
		$value = static::getString($key);
		return $value !== null ? (int) $value : $default;
	}

	public static function set(string $key, string $value, ?string $category = null): void
	{
		$existing = static::query()->where('key', $key)->first();
		$oldValue = $existing?->value;

		static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

		// Log the change if audit logging is enabled and values differ
		if ($oldValue !== $value && auth()->check()) {
			SettingsAuditLog::log($key, $oldValue, $value, $category);
		}
	}

	/**
	 * Get a boolean setting value.
	 */
	public static function getBool(string $key, bool $default = false): bool
	{
		$value = static::getString($key);
		return $value !== null ? (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN) : $default;
	}

	/**
	 * Set a boolean setting value.
	 */
	public static function setBool(string $key, bool $value, ?string $category = null): void
	{
		static::set($key, $value ? '1' : '0', $category);
	}

	/**
	 * Get a JSON setting value.
	 */
	public static function getJson(string $key, ?array $default = null): ?array
	{
		$value = static::getString($key);
		if ($value === null) {
			return $default;
		}
		$decoded = json_decode($value, true);
		return $decoded !== null ? $decoded : $default;
	}

	/**
	 * Set a JSON setting value.
	 */
	public static function setJson(string $key, array $value, ?string $category = null): void
	{
		static::set($key, json_encode($value), $category);
	}
}
