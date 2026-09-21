<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/** Admin panel `files[0..n]` yuboradi, mobil `file` — ikkalasi ham qabul qilinadi. */
class UploadFileRequest extends FormRequest
{
    private const EXT = 'jpg,jpeg,png,webp,svg,pdf,glb';

    public function rules(): array
    {
        return [
            'file' => ['required_without:files', 'file', 'max:61440', 'extensions:'.self::EXT],
            'files' => ['required_without:file', 'array', 'max:10'],
            'files.*' => ['file', 'max:61440', 'extensions:'.self::EXT],
        ];
    }

    /** @return list<UploadedFile> */
    public function uploads(): array
    {
        return $this->hasFile('files') ? $this->file('files') : [$this->file('file')];
    }
}
