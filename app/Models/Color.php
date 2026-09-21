<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use Translatable;

    protected $fillable = ['name_uz', 'name_ru', 'name_en', 'hex', 'sort', 'status'];

    protected $casts = ['status' => Status::class];
}
