<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property string|null $name_uz
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property Status $status
 */
class Permission extends SpatiePermission
{
    use Translatable;

    protected $fillable = ['name', 'guard_name', 'name_uz', 'name_ru', 'name_en', 'status'];

    protected $casts = ['status' => Status::class];
}
