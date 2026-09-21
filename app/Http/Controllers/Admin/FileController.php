<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Support\SvgSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /** Javob har doim massiv: { data: [file, ...] } — admin Upload komponenti shuni kutadi. */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $files = collect($request->uploads())
            ->map(fn (UploadedFile $upload) => $this->persist($upload, $request->user()?->id));

        return response()->json(['data' => FileResource::collection($files)->resolve()], 201);
    }

    private function persist(UploadedFile $upload, ?int $userId): File
    {
        $mime = $upload->getMimeType();
        [$width, $height] = str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml'
            ? (getimagesize($upload->getRealPath()) ?: [null, null])
            : [null, null];

        $path = $upload->store(now()->format('Y/m'), 'public');

        // SVG — skript/XSS'dan tozalab qayta yozamiz (fayl nomi tasodifiy, kengaytma .svg saqlanadi)
        if ($mime === 'image/svg+xml' || strtolower($upload->getClientOriginalExtension()) === 'svg') {
            $clean = SvgSanitizer::clean(file_get_contents($upload->getRealPath()) ?: '');
            Storage::disk('public')->put($path, $clean);
            $mime = 'image/svg+xml';
        }

        return File::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime' => $mime,
            'size' => Storage::disk('public')->size($path),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $userId,
        ]);
    }
}
