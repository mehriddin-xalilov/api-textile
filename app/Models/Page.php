<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Translatable;

    protected $fillable = ['slug', 'title_uz', 'title_ru', 'title_en', 'content_uz', 'content_ru', 'content_en', 'in_footer', 'sort', 'status'];

    protected $casts = ['status' => Status::class, 'in_footer' => 'boolean'];
}
