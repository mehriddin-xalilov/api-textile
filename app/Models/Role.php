<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string|null $name_uz
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property Status $status
 */
class Role extends SpatieRole
{
    use Translatable;

    protected $fillable = ['name', 'guard_name', 'name_uz', 'name_ru', 'name_en', 'status'];

    protected $casts = ['status' => Status::class];
}
