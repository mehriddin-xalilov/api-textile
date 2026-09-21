<?php

namespace Database\Seeders;

use App\Actions\Inventory\SyncProductVariants;
use App\Models\Category;
use App\Models\Color;
use App\Models\File;
use App\Models\GarmentModel;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Sketchfab'dan olingan CC-BY modellar. Fayllar repoda: database/seeders/models/*.glb
 * (seeder ularni storage/app/public/models/ ga ko'chiradi).
 * Muallif nomi saytda ko'rsatilishi shart (CC Attribution).
 */
class GarmentModelLibrarySeeder extends Seeder
{
    private const MODELS = [
        // fayl => [nom uz, ru, en, muallif, mahsulot slug (bog'lash), yangi mahsulot [nom uz, ru, en, kategoriya, narx]]
        'polo.glb' => ['Polo (yoqali)', 'Поло', 'Polo shirt', 'chokybali — sketchfab.com (CC-BY 4.0)', 'polo-classic', null],
        'longsleeve.glb' => ['Uzun yengli futbolka', 'Лонгслив', 'Long sleeve', 'chokybali — sketchfab.com (CC-BY 4.0)', 'longsleeve-classic', null],
        'hoodie.glb' => ['Xudi', 'Худи', 'Hoodie', 'ShoyoX — sketchfab.com (CC-BY 4.0)', 'hoodie-classic', ['Xudi', 'Худи', 'Hoodie', 'sweatshirts', 189000]],
        'cap.glb' => ['Kepka', 'Кепка', 'Baseball cap', 'Scott VanArsdale (vanart) — sketchfab.com (CC-BY 4.0)', 'cap-classic', ['Kepka', 'Кепка', 'Baseball cap', 'caps', 99000]],
        'sweatpants.glb' => ['Triko (shim)', 'Спортивные штаны', 'Sweatpants', 'maxx_renn — sketchfab.com (CC-BY 4.0)', 'sweatpants-classic', ['Sport shim (triko)', 'Спортивные штаны', 'Sweatpants', 'sweatshirts', 149000]],
    ];

    public function run(): void
    {
        foreach (self::MODELS as $filename => [$uz, $ru, $en, $author, $slug, $newProduct]) {
            $path = "models/{$filename}";

            // Modellar repoda `database/seeders/models/` da turadi: yangi serverda storage'ga ko'chiriladi
            if (! Storage::disk('public')->exists($path)) {
                $source = database_path("seeders/models/{$filename}");
                if (! is_file($source)) {
                    $this->command?->warn("{$path} yo'q");

                    continue;
                }
                Storage::disk('public')->put($path, (string) file_get_contents($source));
            }
            $model = GarmentModel::query()->firstWhere('name_en', $en);
            if (! $model) {
                $file = File::query()->create([
                    'disk' => 'public', 'path' => $path, 'original_name' => $filename,
                    'mime' => 'model/gltf-binary', 'size' => Storage::disk('public')->size($path),
                ]);
                $model = GarmentModel::query()->create(['name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en, 'file_id' => $file->id, 'author' => $author]);
            }

            $product = Product::query()->firstWhere('slug', $slug);
            if (! $product && $newProduct) {
                [$puz, $pru, $pen, $cat, $price] = $newProduct;
                $product = Product::query()->create([
                    'category_id' => Category::query()->where('slug', $cat)->value('id'),
                    'name_uz' => $puz, 'name_ru' => $pru, 'name_en' => $pen, 'slug' => $slug,
                    'origin_country' => 'TR', 'gender' => 'unisex', 'base_price' => $price, 'print_price' => 25000,
                ]);
                // ranglar va variantlar: MockupSeeder mantiqi (oq/qora) — kamida bitta rang
                $sync = app(SyncProductVariants::class);
                foreach (Color::query()->whereIn('hex', ['#FFFFFF', '#111111', '#1E3A8A'])->get() as $color) {
                    $pc = $product->colors()->create(['color_id' => $color->id]);
                    $sync->handle($pc);
                    ProductVariant::query()->where('product_color_id', $pc->id)->update(['quantity' => 20]);
                }
                $product->printAreas()->createMany([
                    ['side' => 'front', 'name' => 'Old markaz', 'x' => 30, 'y' => 30, 'width' => 40, 'height' => 40, 'max_width_cm' => 25, 'max_height_cm' => 30],
                    ['side' => 'back', 'name' => 'Orqa markaz', 'x' => 30, 'y' => 26, 'width' => 40, 'height' => 45, 'max_width_cm' => 28, 'max_height_cm' => 35],
                ]);
            }
            $product?->update(['garment_model_id' => $model->id]);
        }
    }
}
