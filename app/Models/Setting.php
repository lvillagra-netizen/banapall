<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'json' => json_decode((string) $setting->value, true) ?: $default,
            default => $setting->value ?? $default,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $description = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                'group' => $group,
                'type' => $type,
                'description' => $description,
            ],
        );
    }
}
