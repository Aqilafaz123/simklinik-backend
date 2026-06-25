<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'k';

    protected $keyType = 'string';

    protected $fillable = ['k', 'v'];

    public static function allKeyed(): array
    {
        return Cache::remember('app_settings', 300, function () {
            try {
                return static::query()->pluck('v', 'k')->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function get(string $key, string $default = ''): string
    {
        $settings = static::allKeyed();

        return $settings[$key] ?? $default;
    }

    public static function flushCache(): void
    {
        Cache::forget('app_settings');
    }
}
