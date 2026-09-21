<?php

namespace Database\Seeders;

use App\Actions\Inventory\SyncProductVariants;
use App\Models\Category;
use App\Models\Color;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Haqiqiy fotolar kelguncha: vektor mockuplar (soya, burma, mato teksturasi) — 3 kiyim turi × 4 ko'rinish × har rang.
 * Rasm yuklangan rangga tegilmaydi. Haqiqiy foto yuklansa avtomatik almashadi.
 */
class MockupSeeder extends Seeder
{
    private const PRODUCTS = [
        // slug => [kind, category slug, nom uz/ru/en, narx, bosma narx, mato]
        'classic-tshirt' => ['tshirt', 'tshirts', ['Klassik futbolka', 'Классическая футболка', 'Classic T-shirt'], 89000, 25000, '100% paxta, 180 gsm'],
        'polo-classic' => ['polo', 'polo', ['Polo (yoqali)', 'Поло', 'Polo shirt'], 129000, 25000, 'Pike paxta, 220 gsm'],
        'longsleeve-classic' => ['longsleeve', 'sweatshirts', ['Uzun yengli futbolka', 'Лонгслив', 'Long sleeve'], 109000, 25000, '100% paxta, 200 gsm'],
        // xudi va triko uchun vektor mockup yo'q — uzun yengli/uzun yeng shabloni vaqtincha (3D asosiy ko'rinish)
        'hoodie-classic' => ['longsleeve', 'sweatshirts', ['Xudi', 'Худи', 'Hoodie'], 189000, 25000, 'Fliz, 320 gsm'],
        'sweatpants-classic' => ['longsleeve', 'sweatshirts', ['Sport shim (triko)', 'Спортивные штаны', 'Sweatpants'], 149000, 25000, 'Fliz, 300 gsm'],
    ];

    private const VIEWS = ['front' => 'front_image_id', 'back' => 'back_image_id', 'left' => 'left_image_id', 'right' => 'right_image_id'];

    public function run(): void
    {
        $sync = app(SyncProductVariants::class);
        $colors = Color::query()->orderBy('sort')->get();

        foreach (self::PRODUCTS as $slug => [$kind, $categorySlug, [$uz, $ru, $en], $price, $printPrice, $fabric]) {
            $product = Product::query()->firstOrCreate(['slug' => $slug], [
                'category_id' => Category::query()->where('slug', $categorySlug)->value('id'),
                'name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en,
                'fabric' => $fabric, 'origin_country' => 'TR', 'gender' => 'unisex',
                'base_price' => $price, 'print_price' => $printPrice,
            ]);

            if ($product->printAreas()->doesntExist()) {
                $product->printAreas()->createMany($this->printAreas($kind));
            }

            foreach ($colors as $color) {
                $pc = ProductColor::query()->firstOrCreate(['product_id' => $product->id, 'color_id' => $color->id]);
                if ($pc->wasRecentlyCreated) {
                    $sync->handle($pc);
                }
                foreach (self::VIEWS as $view => $column) {
                    $current = $pc->{$column} ? File::query()->find($pc->{$column}) : null;
                    if ($current && ! str_starts_with($current->path, 'mockups/')) {
                        continue; // haqiqiy foto yuklangan — tegmaymiz
                    }
                    $pc->{$column} = $this->mockupFile($kind, $view, $color, $current)->id;
                }
                $pc->save();
            }
        }
    }

    private function mockupFile(string $kind, string $view, Color $color, ?File $existing = null): File
    {
        $svg = str_replace('{{COLOR}}', $color->hex, file_get_contents(database_path("seeders/mockups/{$kind}-{$view}.svg")));
        $path = sprintf('mockups/%s-%s-%s.svg', $kind, $view, ltrim($color->hex, '#'));
        Storage::disk('public')->put($path, $svg);

        if ($existing) {
            $existing->update(['size' => strlen($svg)]);

            return $existing;
        }

        return File::query()->create([
            'disk' => 'public', 'path' => $path, 'original_name' => basename($path),
            'mime' => 'image/svg+xml', 'size' => strlen($svg), 'width' => 1000, 'height' => 1000,
        ]);
    }

    /** Bosma joylari mockup foizida (viewBox 1000). Polo'da ko'krak markazi placket tufayli kichikroq. */
    private function printAreas(string $kind): array
    {
        $front = $kind === 'polo'
            ? [['side' => 'front', 'name' => "Ko'krak chap", 'x' => 54, 'y' => 30, 'width' => 14, 'height' => 12, 'max_width_cm' => 10, 'max_height_cm' => 10]]
            : [
                ['side' => 'front', 'name' => "Ko'krak chap", 'x' => 54, 'y' => 27, 'width' => 14, 'height' => 12, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ['side' => 'front', 'name' => 'Old markaz', 'x' => 30, 'y' => 30, 'width' => 40, 'height' => 45, 'max_width_cm' => 28, 'max_height_cm' => 35],
            ];
        $back = [['side' => 'back', 'name' => 'Orqa markaz', 'x' => 30, 'y' => 26, 'width' => 40, 'height' => 48, 'max_width_cm' => 28, 'max_height_cm' => 38]];
        $sleeve = $kind === 'longsleeve'
            ? [['side' => 'left_sleeve', 'name' => 'Chap yeng', 'x' => 26, 'y' => 30, 'width' => 8, 'height' => 30, 'max_width_cm' => 7, 'max_height_cm' => 25],
                ['side' => 'right_sleeve', 'name' => "O'ng yeng", 'x' => 66, 'y' => 30, 'width' => 8, 'height' => 30, 'max_width_cm' => 7, 'max_height_cm' => 25]]
            : [['side' => 'left_sleeve', 'name' => 'Chap yeng', 'x' => 27, 'y' => 30, 'width' => 9, 'height' => 9, 'max_width_cm' => 8, 'max_height_cm' => 8],
                ['side' => 'right_sleeve', 'name' => "O'ng yeng", 'x' => 64, 'y' => 30, 'width' => 9, 'height' => 9, 'max_width_cm' => 8, 'max_height_cm' => 8]];

        return [...$front, ...$back, ...$sleeve];
    }
}
