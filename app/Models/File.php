<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'width', 'height', 'uploaded_by'];

    protected $appends = ['src'];

    public function getSrcAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
