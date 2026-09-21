<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['about', 'Biz haqimizda', 'О нас', 'About us', "<p><strong>Textile</strong> — o'z dizayningizdagi kiyim. Futbolka, xudi, kepka va boshqa kiyimlarga yozuv yoki logotipingizni bosamiz, tikamiz va yetkazib beramiz.</p><p>Har bir buyurtma alohida tayyorlanadi: 1 donadan boshlab, korporativ buyurtmalar uchun chegirmalar.</p>", '<p><strong>Textile</strong> — качественная одежда без логотипов из Турции и Китая. Вы добавляете свой логотип или надпись в 3D-конструкторе, мы печатаем, шьём и доставляем.</p>', '<p><strong>Textile</strong> — quality blank apparel from Turkey and China. Add your logo or text in the 3D designer, we print, sew and deliver.</p>'],
            ['contact', 'Aloqa', 'Контакты', 'Contact', '<p>Telefon: <a href="tel:+998901234567">+998 90 123 45 67</a><br>Telegram: <a href="https://t.me/textile_uz">@textile_uz</a><br>Manzil: Toshkent, Chilonzor</p><p>Ish vaqti: Du–Sha 9:00–19:00</p>', '<p>Телефон: +998 90 123 45 67<br>Telegram: @textile_uz<br>Адрес: Ташкент, Чиланзар</p>', '<p>Phone: +998 90 123 45 67<br>Telegram: @textile_uz<br>Address: Tashkent, Chilanzar</p>'],
            ['delivery', 'Yetkazib berish va qaytarish', 'Доставка и возврат', 'Delivery & returns', "<p>Toshkent bo'ylab 1 kun, viloyatlarga 2–4 kun. Buyurtma tasdiqlangach tikiladi va jo'natiladi.</p><p>Bosma qilingan (shaxsiy dizaynli) mahsulotlar faqat ishlab chiqarish nuqsoni bo'lsa qaytariladi — 7 kun ichida.</p>", '<p>По Ташкенту 1 день, по регионам 2–4 дня.</p>', '<p>Tashkent 1 day, regions 2–4 days.</p>'],
            ['offer', 'Ommaviy oferta', 'Публичная оферта', 'Public offer', '<p>Ommaviy oferta matni (yuridik bo\'lim tomonidan to\'ldiriladi).</p>', '<p>Текст публичной оферты.</p>', '<p>Public offer text.</p>'],
        ] as $i => [$slug, $uz, $ru, $en, $cuz, $cru, $cen]) {
            Page::query()->firstOrCreate(['slug' => $slug], ['title_uz' => $uz, 'title_ru' => $ru, 'title_en' => $en, 'content_uz' => $cuz, 'content_ru' => $cru, 'content_en' => $cen, 'sort' => $i]);
        }

        if (Banner::query()->doesntExist()) {
            Banner::query()->create(['title_uz' => "O'z dizayningizdagi kiyim", 'title_ru' => 'Одежда с вашим дизайном', 'title_en' => 'Apparel with your design', 'subtitle_uz' => 'Futbolka, xudi, kepka — yozuvingiz yoki logotipingiz bilan. Bir donadan buyurtma qiling, tikib eshigingizgacha yetkazamiz.', 'subtitle_ru' => 'Футболки, худи, кепки — с вашей надписью или логотипом. Заказ от одной штуки, сошьём и доставим до двери.', 'subtitle_en' => 'T-shirts, hoodies, caps — with your text or logo. Order from a single piece, we sew and deliver.', 'button_text_uz' => 'Dizayn qilishni boshlash', 'button_text_ru' => 'Начать дизайн', 'button_text_en' => 'Start designing', 'link' => '/studio', 'sort' => 0]);
            Banner::query()->create(['title_uz' => 'Tayyor mahsulotlar', 'title_ru' => 'Готовые товары', 'title_en' => 'Ready-made products', 'subtitle_uz' => 'Bosilgan va tikilgan holda turibdi — tanlang, razmerni ayting, ertaga kiyasiz.', 'subtitle_ru' => 'Уже отпечатано и сшито — выберите размер и носите уже завтра.', 'subtitle_en' => 'Printed and sewn, ready to ship — pick your size and wear it tomorrow.', 'button_text_uz' => 'Tayyor mahsulotlar', 'button_text_ru' => 'Готовые товары', 'button_text_en' => 'Ready products', 'link' => '/ready', 'sort' => 1]);
        }

        foreach (['phone' => '+998 90 123 45 67', 'telegram' => 'https://t.me/textile_uz', 'instagram' => 'https://instagram.com/textile.uz', 'email' => 'info@textile.uz', 'address_uz' => 'Toshkent, Chilonzor', 'address_ru' => 'Ташкент, Чиланзар', 'address_en' => 'Tashkent, Chilanzar', 'work_hours' => 'Du–Sha 9:00–19:00', 'delivery_text_uz' => 'Toshkent 1 kun, viloyatlar 2–4 kun', 'delivery_text_ru' => 'Ташкент 1 день, регионы 2–4 дня', 'delivery_text_en' => 'Tashkent 1 day, regions 2–4 days'] as $k => $v) {
            Setting::query()->firstOrCreate(['key' => $k], ['value' => $v, 'group' => 'contact']);
        }
        Setting::flush();
    }
}
