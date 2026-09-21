<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'group'];

    /** @return array<string, string|null> */
    public static function all_(): array
    {
        return Cache::remember('settings', 300, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function flush(): void
    {
        Cache::forget('settings');
    }
}
