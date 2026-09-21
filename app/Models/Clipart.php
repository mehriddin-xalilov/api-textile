<?php

namespace App\Models;

use App\Enums\Status;
use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clipart extends Model
{
    use Translatable;

    protected $fillable = ['name_uz', 'name_ru', 'name_en', 'category', 'file_id', 'recolorable', 'sort', 'status'];

    protected $casts = ['status' => Status::class, 'recolorable' => 'boolean'];

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
