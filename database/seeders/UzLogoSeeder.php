<?php

namespace Database\Seeders;

use App\Models\Clipart;
use App\Models\File;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * O'zbekiston brendlari va sport klublari logolari (Wikimedia'dan). Eski cliparts o'chiriladi.
 * Manifest: database/seeders/uzlogos/manifest.json {key: [fayl, nom, kategoriya, hajm]}.
 * Ogohlantirish: tovar belgilari egalariga tegishli — tijoriy bosma uchun ruxsat masalasi mijoz zimmasida.
 */
class UzLogoSeeder extends Seeder
{
    private const RU = ['sport' => 'Спорт', 'brand' => 'Бренд', 'uzbek' => 'Узбекистан'];

    public function run(): void
    {
        Clipart::query()->delete();

        $manifest = json_decode(file_get_contents(database_path('seeders/uzlogos/manifest.json')), true);
        $i = 0;
        foreach ($manifest as $key => [$file, $name, $category]) {
            $source = database_path("seeders/uzlogos/{$file}");
            if (! file_exists($source)) {
                continue;
            }
            $path = "cliparts/uz/{$file}";
            Storage::disk('public')->put($path, file_get_contents($source));
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $f = File::query()->create([
                'disk' => 'public', 'path' => $path, 'original_name' => $file,
                'mime' => $ext === 'svg' ? 'image/svg+xml' : 'image/png', 'size' => filesize($source),
            ]);
            Clipart::query()->create([
                'name_uz' => $name, 'name_ru' => $name, 'name_en' => $name,
                'category' => $category, 'file_id' => $f->id, 'recolorable' => false, 'sort' => $i++,
            ]);
        }
    }
}
