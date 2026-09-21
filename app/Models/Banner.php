<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use Translatable;

    protected $fillable = ['title_uz', 'title_ru', 'title_en', 'subtitle_uz', 'subtitle_ru', 'subtitle_en', 'button_text_uz', 'button_text_ru', 'button_text_en', 'link', 'image_id', 'sort', 'status'];

    protected $casts = ['status' => Status::class];

    /** @return BelongsTo<File, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }
}
