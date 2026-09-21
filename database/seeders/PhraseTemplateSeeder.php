<?php

namespace Database\Seeders;

use App\Models\PhraseTemplate;
use Illuminate\Database\Seeder;

/**
 * Boshlang'ich trend so'zlar. Kontent jamoasi admin orqali yangilab turadi —
 * bu ro'yxat faqat start. [matn, kategoriya, shrift, og'irlik, uslub, rang]
 */
class PhraseTemplateSeeder extends Seeder
{
    private const ITEMS = [
        ["Bo'ladi hammasi", 'motivation', 'Bebas Neue', '400', 'normal', '#111111'],
        ["Hammasi zo'r", 'trend', 'Anton', '400', 'normal', '#dc2626'],
        ['Yaxshi odam', 'trend', 'Oswald', '700', 'normal', '#111111'],
        ['Toshkent shahar', 'uzbek', 'Bebas Neue', '400', 'normal', '#1d4ed8'],
        ['UZB', 'uzbek', 'Anton', '400', 'normal', '#0ea5e9'],
        ['Qaytmas', 'motivation', 'Oswald', '700', 'italic', '#111111'],
        ['Ishla, keyin dam ol', 'motivation', 'Montserrat', '900', 'normal', '#111111'],
        ['Mehnat — baxt', 'motivation', 'Playfair Display', '700', 'italic', '#111111'],
        ['Non yeganim rost', 'humor', 'Permanent Marker', '400', 'normal', '#111111'],
        ["Osh bo'lsin", 'humor', 'Lobster', '400', 'normal', '#dc2626'],
        ['Choy ichamizmi?', 'humor', 'Caveat', '700', 'normal', '#111111'],
        ["Men o'zbekman", 'uzbek', 'Oswald', '700', 'normal', '#16a34a'],
        ['Vatan — bitta', 'uzbek', 'Bebas Neue', '400', 'normal', '#111111'],
        ['Samarqand', 'uzbek', 'Playfair Display', '700', 'normal', '#1d4ed8'],
        ['Buxoro', 'uzbek', 'Playfair Display', '700', 'normal', '#7c3aed'],
        ['Ona', 'love', 'Dancing Script', '700', 'normal', '#db2777'],
        ['Oila — hammasi', 'love', 'Pacifico', '400', 'normal', '#dc2626'],
        ['Sevgi bor', 'love', 'Caveat', '700', 'normal', '#dc2626'],
        ['Chempion', 'sport', 'Anton', '400', 'italic', '#111111'],
        ['Kurash', 'sport', 'Bebas Neue', '400', 'normal', '#111111'],
        ['Sport — hayot', 'sport', 'Oswald', '700', 'normal', '#0f766e'],
        ['Dangasa emasman, dam olyapman', 'humor', 'Caveat', '400', 'normal', '#111111'],
        ["Kayfiyat a'lo", 'trend', 'Pacifico', '400', 'normal', '#f97316'],
        ['Bugun — mening kunim', 'motivation', 'Montserrat', '700', 'normal', '#111111'],
        ['Est. 2026', 'trend', 'Playfair Display', '400', 'italic', '#111111'],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $i => [$text, $category, $font, $weight, $style, $fill]) {
            PhraseTemplate::query()->firstOrCreate(['text' => $text], [
                'category' => $category, 'font_family' => $font, 'font_weight' => $weight, 'font_style' => $style, 'fill' => $fill, 'sort' => $i,
            ]);
        }
    }
}
