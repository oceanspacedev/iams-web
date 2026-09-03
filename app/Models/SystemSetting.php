<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting value by key with optional default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $val = $setting->value;
        if ($val === 'true') return true;
        if ($val === 'false') return false;
        if (is_numeric($val)) return $val + 0;

        return $val;
    }

    /**
     * Set or update a setting by key.
     */
    public static function set(string $key, mixed $value, ?string $description = null): static
    {
        if (is_bool($value)) {
            $formattedValue = $value ? 'true' : 'false';
        } else {
            $formattedValue = (string) $value;
        }

        $data = ['value' => $formattedValue];
        if ($description !== null) {
            $data['description'] = $description;
        }

        return static::updateOrCreate(['key' => $key], $data);
    }

    /**
     * Check if demo accounts display is enabled.
     */
    public static function isDemoAccountsEnabled(): bool
    {
        return (bool) static::get('show_demo_accounts', true);
    }
}
