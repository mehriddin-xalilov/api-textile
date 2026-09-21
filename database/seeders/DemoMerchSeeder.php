<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\ReadyProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * DEMO MA'LUMOT — faqat MVP ko'rsatish uchun.
 *
 * Rasmlar va mahsulot nomlari tirikchilik.uz do'konidan olingan vaqtinchalik namunalar
 * (`storage/app/public/demo/tirikchilik`). Ular bizning mulkimiz emas: saytni real
 * ishga tushirishdan oldin o'z mahsulot fotolari bilan almashtirilishi shart.
 * O'chirish: `php artisan db:seed --class=DemoMerchSeeder` emas, balki
 * `Design::where('template_title', 'like', '[demo]%')->delete()`.
 */
class DemoMerchSeeder extends Seeder
{
    /** Ularning kategoriyasi → bizning mahsulot slug'i. */
    /** Odam tushgan yoki kiyim bo'lmagan rasmlar (qo'lda tekshirilgan) — kartochkaga yaramaydi. */
    private const SKIP = [3, 6, 9, 10, 11, 24, 25, 26, 27, 58, 63, 64, 66, 67, 68, 69, 70, 91, 92, 93, 94, 95, 96, 163];

    /** Demo shablonlar ro'yxat boshida tursin. */
    private const SORT_START = 1;

    /** Razmerlar: kiyim turiga qarab. */
    private const SIZES = [
        'Kepkalar' => ['UNI'],
        'Shalvar' => ['S', 'M', 'L', 'XL'],
        'Shortilar' => ['S', 'M', 'L', 'XL'],
    ];

    private const DEFAULT_SIZES = ['S', 'M', 'L', 'XL', 'XXL'];

    /** Nomdagi emoji va ortiqcha belgilarni olib tashlaydi (kartochkada toza ko'rinsin). */
    private static function cleanTitle(string $name): string
    {
        $name = (string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{2B00}-\x{2BFF}]/u', '', $name);

        return trim((string) preg_replace('/\s*[—-]\s*$/u', '', trim($name)));
    }

    /** Demo mahsulotlarni bazadan olib tashlash. */
    public static function purge(): int
    {
        $ids = ReadyProduct::query()
            ->whereHas('images.file', fn ($q) => $q->where('path', 'like', 'demo/tirikchilik/%'))
            ->pluck('id');

        return ReadyProduct::query()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Namuna xususiyatlar jadvali.
     *
     * @return list<array{name: string, value: string}>
     */
    private static function specs(string $category): array
    {
        $base = [
            ['name' => 'Mato', 'value' => '100% paxta, 190 g/m²'],
            ['name' => 'Bosma', 'value' => "DTG — yuvishda o'chmaydi"],
            ['name' => 'Parvarish', 'value' => '30°C da yuvish, past haroratda dazmollash'],
            ['name' => 'Ishlab chiqarilgan', 'value' => "O'zbekiston"],
        ];

        return match ($category) {
            'Kepkalar' => [
                ['name' => 'Mato', 'value' => 'Kotton twill'],
                ['name' => "O'lcham", 'value' => 'UNI (regulyator bilan)'],
                ...array_slice($base, 1),
            ],
            'Xudi' => [
                ['name' => 'Mato', 'value' => 'Uch ipli futer, 320 g/m²'],
                ['name' => 'Fason', 'value' => 'Oversize, kengaytirilgan yeng'],
                ...array_slice($base, 1),
            ],
            default => $base,
        };
    }

    public function run(): void
    {
        $path = database_path('seeders/data/tirikchilik-demo.json');
        if (! is_file($path)) {
            $this->command?->warn('Demo fayl yo\'q: '.$path);

            return;
        }

        /** @var array{data: array{value: list<array<string, mixed>>}} $json */
        $json = json_decode((string) file_get_contents($path), true);
        $items = $json['data']['value'] ?? [];

        $admin = User::query()->orderBy('id')->firstOrFail();
        $sort = self::SORT_START;
        $made = 0;

        foreach ($items as $row) {
            if (in_array((int) $row['productId'], self::SKIP, true)) {
                continue;
            }

            $ext = pathinfo((string) $row['imgUrl'], PATHINFO_EXTENSION) ?: 'png';
            $rel = "demo/tirikchilik/{$row['productId']}.{$ext}";
            if (! Storage::disk('public')->exists($rel)) {
                continue;
            }

            $file = File::query()->firstOrCreate(
                ['disk' => 'public', 'path' => $rel],
                [
                    'original_name' => "demo-{$row['productId']}.{$ext}",
                    'mime' => $ext === 'png' ? 'image/png' : 'image/jpeg',
                    'size' => Storage::disk('public')->size($rel),
                    'uploaded_by' => $admin->id,
                ],
            );

            $category = (string) ($row['categoryNameUz'] ?? '');
            $title = self::cleanTitle((string) ($row['productNameUz'] ?: $row['productName']));

            $product = ReadyProduct::query()->updateOrCreate(
                ['slug' => 'demo-'.$row['productId']],
                [
                    'name_uz' => $title,
                    'name_ru' => $title,
                    'name_en' => $title,
                    'description_uz' => $category,
                    'price' => (int) ($row['productPrice'] ?? 0) / 100,
                    'sizes' => self::SIZES[$category] ?? self::DEFAULT_SIZES,
                    'specs' => self::specs($category),
                    'quantity' => 0,
                    'status' => 'active',
                    'sort' => $sort++,
                ],
            );

            if ($product->images()->count() === 0) {
                $product->images()->create(['file_id' => $file->id, 'sort' => 0]);
            }
            $made++;
        }

        $this->command?->info("Demo merch: {$made} ta tayyor mahsulot (tirikchilik namunalari).");
    }
}
