<?php

namespace Database\Seeders;

use App\Actions\Inventory\SyncProductVariants;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Seeder;

/** Boshlang'ich lug'atlar: razmerlar, ranglar, kategoriyalar. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'] as $i => $name) {
            Size::query()->firstOrCreate(['name' => $name], ['sort' => $i]);
        }

        $colors = [
            ['Oq', 'Белый', 'White', '#FFFFFF'],
            ['Qora', 'Чёрный', 'Black', '#111111'],
            ['Kulrang', 'Серый', 'Gray', '#9CA3AF'],
            ["To'q ko'k", 'Тёмно-синий', 'Navy', '#1E3A8A'],
            ['Qizil', 'Красный', 'Red', '#DC2626'],
            ['Bej', 'Бежевый', 'Beige', '#E7DCC8'],
        ];
        foreach ($colors as $i => [$uz, $ru, $en, $hex]) {
            Color::query()->firstOrCreate(['hex' => $hex], ['name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en, 'sort' => $i]);
        }

        $categories = [
            ['Futbolkalar', 'Футболки', 'T-shirts', 'tshirts'],
            ['Xudi', 'Худи', 'Hoodies', 'hoodies'],
            ['Polo', 'Поло', 'Polo', 'polo'],
            ['Svitshot', 'Свитшот', 'Sweatshirts', 'sweatshirts'],
            ['Kepkalar', 'Кепки', 'Caps', 'caps'],
        ];
        foreach ($categories as $i => [$uz, $ru, $en, $slug]) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en, 'sort' => $i]);
        }

        if (app()->isProduction() || Product::query()->exists()) {
            return;
        }

        // Namuna mahsulot (faqat local).
        $product = Product::query()->create([
            'category_id' => Category::query()->where('slug', 'tshirts')->value('id'),
            'name_uz' => 'Klassik futbolka', 'name_ru' => 'Классическая футболка', 'name_en' => 'Classic T-shirt',
            'slug' => 'classic-tshirt',
            'description_uz' => '100% paxta, 180 gsm. Logosiz, bosma uchun ideal.',
            'fabric' => '100% paxta, 180 gsm', 'origin_country' => 'TR', 'gender' => 'unisex',
            'base_price' => 89000, 'print_price' => 25000,
        ]);

        $product->printAreas()->createMany([
            ['side' => 'front', 'name' => "Ko'krak chap", 'x' => 55, 'y' => 22, 'width' => 14, 'height' => 12, 'max_width_cm' => 10, 'max_height_cm' => 10],
            ['side' => 'front', 'name' => 'Old markaz', 'x' => 30, 'y' => 25, 'width' => 40, 'height' => 40, 'max_width_cm' => 28, 'max_height_cm' => 35],
            ['side' => 'back', 'name' => 'Orqa markaz', 'x' => 30, 'y' => 20, 'width' => 40, 'height' => 45, 'max_width_cm' => 28, 'max_height_cm' => 38],
        ]);

        $sync = app(SyncProductVariants::class);
        foreach (Color::query()->whereIn('hex', ['#FFFFFF', '#111111'])->get() as $color) {
            $sync->handle($product->colors()->create(['color_id' => $color->id]));
        }
    }
}
