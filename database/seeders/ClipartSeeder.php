<?php

namespace Database\Seeders;

use App\Models\Clipart;
use App\Models\File;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Boshlang'ich tayyor logolar (bir rangli SVG — rangi konstruktorda o'zgaradi). */
class ClipartSeeder extends Seeder
{
    private const ITEMS = [
        // Professional duotone ikonlar (Solar icons, CC BY 4.0 — 480 Design)
        'pro-basketball' => ['Basketbol', 'Баскетбол', 'Basketball', 'sport'],
        'pro-football' => ['Futbol', 'Футбол', 'Football', 'sport'],
        'pro-rugby' => ['Regbi', 'Регби', 'Rugby', 'sport'],
        'pro-golf' => ['Golf', 'Гольф', 'Golf', 'sport'],
        'pro-bowling' => ['Bouling', 'Боулинг', 'Bowling', 'sport'],
        'pro-dumbbell' => ['Gantel', 'Гантель', 'Dumbbell', 'sport'],
        'pro-dumbbells' => ['Gantellar', 'Гантели', 'Dumbbells', 'sport'],
        'pro-skateboarding' => ['Skeytbord', 'Скейтборд', 'Skateboarding', 'sport'],
        'pro-running' => ['Yugurish', 'Бег', 'Running', 'sport'],
        'pro-bicycling' => ['Velosport', 'Велоспорт', 'Cycling', 'sport'],
        'pro-hiking' => ['Piyoda yurish', 'Поход', 'Hiking', 'sport'],
        'pro-meditation' => ['Meditatsiya', 'Медитация', 'Meditation', 'sport'],
        'pro-medal-star' => ['Medal', 'Медаль', 'Medal', 'sport'],
        'pro-medal-ribbon-star' => ['Medal (lenta)', 'Медаль с лентой', 'Medal ribbon', 'sport'],
        'pro-ranking' => ['Reyting', 'Рейтинг', 'Ranking', 'sport'],
        'pro-scooter' => ['Samokat', 'Самокат', 'Scooter', 'sport'],
        'pro-kick-scooter' => ['Trotinet', 'Самокат', 'Kick scooter', 'sport'],
        'pro-rocket' => ['Raketa', 'Ракета', 'Rocket', 'kosmos'],
        'pro-rocket2' => ['Raketa 2', 'Ракета 2', 'Rocket 2', 'kosmos'],
        'pro-planet' => ['Sayyora', 'Планета', 'Planet', 'kosmos'],
        'pro-planet2' => ['Sayyora 2', 'Планета 2', 'Planet 2', 'kosmos'],
        'pro-asteroid' => ['Asteroid', 'Астероид', 'Asteroid', 'kosmos'],
        'pro-black-hole' => ['Qora tuynuk', 'Чёрная дыра', 'Black hole', 'kosmos'],
        'pro-satellite' => ['Sun\'iy yo\'ldosh', 'Спутник', 'Satellite', 'kosmos'],
        'pro-atom' => ['Atom', 'Атом', 'Atom', 'kosmos'],
        'pro-dna' => ['DNK', 'ДНК', 'DNA', 'kosmos'],
        'pro-earth' => ['Yer', 'Земля', 'Earth', 'kosmos'],
        'pro-global' => ['Globus', 'Глобус', 'Global', 'kosmos'],
        'pro-moon-stars' => ['Oy va yulduzlar', 'Луна и звёзды', 'Moon & stars', 'kosmos'],
        'pro-ufo' => ['UFO', 'НЛО', 'UFO', 'kosmos'],
        'pro-crown' => ['Toj', 'Корона', 'Crown', 'belgilar'],
        'pro-crown-star' => ['Toj (yulduz)', 'Корона со звездой', 'Crown star', 'belgilar'],
        'pro-fire' => ['Olov', 'Огонь', 'Fire', 'belgilar'],
        'pro-flame' => ['Alanga', 'Пламя', 'Flame', 'belgilar'],
        'pro-bolt' => ['Chaqmoq', 'Молния', 'Bolt', 'belgilar'],
        'pro-lightning' => ['Yashin', 'Молния', 'Lightning', 'belgilar'],
        'pro-star' => ['Yulduz', 'Звезда', 'Star', 'belgilar'],
        'pro-stars' => ['Yulduzlar', 'Звёзды', 'Stars', 'belgilar'],
        'pro-heart' => ['Yurak', 'Сердце', 'Heart', 'belgilar'],
        'pro-heart-shine' => ['Yurak (porlash)', 'Сердце сияние', 'Heart shine', 'belgilar'],
        'pro-hearts' => ['Yuraklar', 'Сердца', 'Hearts', 'belgilar'],
        'pro-magic-stick' => ['Sehrli tayoq', 'Волшебная палочка', 'Magic stick', 'belgilar'],
        'pro-confetti' => ['Konfetti', 'Конфетти', 'Confetti', 'belgilar'],
        'pro-ghost' => ['Arvoh', 'Привидение', 'Ghost', 'belgilar'],
        'pro-ghost-smile' => ['Arvoh (kulgich)', 'Привидение улыбка', 'Ghost smile', 'belgilar'],
        'pro-shield' => ['Qalqon', 'Щит', 'Shield', 'belgilar'],
        'pro-shield-star' => ['Qalqon (yulduz)', 'Щит со звездой', 'Shield star', 'belgilar'],
        'pro-leaf' => ['Barg', 'Лист', 'Leaf', 'tabiat'],
        'pro-snowflake' => ['Qor uchquni', 'Снежинка', 'Snowflake', 'tabiat'],
        'pro-sun' => ['Quyosh', 'Солнце', 'Sun', 'tabiat'],
        'pro-moon' => ['Oy', 'Луна', 'Moon', 'tabiat'],
        'pro-cloud-sun' => ['Bulutli quyosh', 'Облачно', 'Cloud sun', 'tabiat'],
        'pro-cloud-rain' => ['Yomg\'ir', 'Дождь', 'Rain', 'tabiat'],
        'pro-cloud-storm' => ['Bo\'ron', 'Гроза', 'Storm', 'tabiat'],
        'pro-bonfire' => ['Gulxan', 'Костёр', 'Bonfire', 'tabiat'],
        'pro-waterdrop' => ['Tomchi', 'Капля', 'Waterdrop', 'tabiat'],
        'pro-paw' => ['Panja', 'Лапа', 'Paw', 'tabiat'],
        'pro-cat' => ['Mushuk', 'Кот', 'Cat', 'tabiat'],
        'pro-bone' => ['Suyak', 'Кость', 'Bone', 'tabiat'],
        'pro-bug' => ['Hasharot', 'Жук', 'Bug', 'tabiat'],
        'pro-cup-hot' => ['Issiq qahva', 'Горячий кофе', 'Hot cup', 'ovqat'],
        'pro-donut' => ['Donat', 'Пончик', 'Donut', 'ovqat'],
        'pro-chef-hat' => ['Oshpaz qalpog\'i', 'Колпак повара', 'Chef hat', 'ovqat'],
        'pro-plate' => ['Laganda', 'Тарелка', 'Plate', 'ovqat'],
        'pro-ladle' => ['Cho\'mich', 'Половник', 'Ladle', 'ovqat'],
        'pro-rolling-pin' => ['O\'qlog\'och', 'Скалка', 'Rolling pin', 'ovqat'],
        'pro-bottle' => ['Shisha', 'Бутылка', 'Bottle', 'ovqat'],
        'pro-cup-star' => ['Kubok', 'Кубок', 'Cup star', 'ovqat'],
        'pro-cup' => ['Piyola', 'Чашка', 'Cup', 'ovqat'],
        'pro-music-note' => ['Nota', 'Нота', 'Music note', 'musiqa'],
        'pro-music-notes' => ['Notalar', 'Ноты', 'Music notes', 'musiqa'],
        'pro-headphones-round' => ['Quloqchin', 'Наушники', 'Headphones', 'musiqa'],
        'pro-microphone' => ['Mikrofon', 'Микрофон', 'Microphone', 'musiqa'],
        'pro-cassette' => ['Kasseta', 'Кассета', 'Cassette', 'musiqa'],
        'pro-boombox' => ['Magnitofon', 'Бумбокс', 'Boombox', 'musiqa'],
        'pro-radio' => ['Radio', 'Радио', 'Radio', 'musiqa'],
        'pro-playlist' => ['Pleylist', 'Плейлист', 'Playlist', 'musiqa'],
        'pro-soundwave' => ['Tovush', 'Звуковая волна', 'Soundwave', 'musiqa'],
        'pro-gamepad' => ['Geympad', 'Геймпад', 'Gamepad', 'texnika'],
        'pro-gameboy' => ['Geymboy', 'Геймбой', 'Gameboy', 'texnika'],
        'pro-camera' => ['Kamera', 'Камера', 'Camera', 'texnika'],
        'pro-cpu' => ['Protsessor', 'Процессор', 'CPU', 'texnika'],
        'pro-code' => ['Kod', 'Код', 'Code', 'texnika'],
        'pro-programming' => ['Dasturlash', 'Программирование', 'Programming', 'texnika'],
        'pro-smartphone' => ['Telefon', 'Смартфон', 'Smartphone', 'texnika'],
        'pro-laptop' => ['Noutbuk', 'Ноутбук', 'Laptop', 'texnika'],
        'pro-qr-code' => ['QR kod', 'QR-код', 'QR code', 'texnika'],
        'pro-bluetooth' => ['Bluetooth', 'Bluetooth', 'Bluetooth', 'texnika'],
        'pro-compass' => ['Kompas', 'Компас', 'Compass', 'shapes'],
        'pro-map' => ['Xarita', 'Карта', 'Map', 'shapes'],
        'pro-map-point' => ['Joylashuv', 'Локация', 'Map point', 'shapes'],
        'pro-key' => ['Kalit', 'Ключ', 'Key', 'shapes'],
        'pro-lightbulb' => ['Lampochka', 'Лампочка', 'Lightbulb', 'shapes'],
        'pro-palette' => ['Palitra', 'Палитра', 'Palette', 'shapes'],
        'pro-pen' => ['Ruchka', 'Ручка', 'Pen', 'shapes'],
        'pro-scissors' => ['Qaychi', 'Ножницы', 'Scissors', 'shapes'],
        'pro-eye' => ['Ko\'z', 'Глаз', 'Eye', 'shapes'],
        'pro-hanger' => ['Ilgich', 'Вешалка', 'Hanger', 'shapes'],
        'pro-glasses' => ['Ko\'zoynak', 'Очки', 'Glasses', 'shapes'],
        'pro-backpack' => ['Ryukzak', 'Рюкзак', 'Backpack', 'shapes'],
        'pro-balloon' => ['Shar', 'Шар', 'Balloon', 'shapes'],
        'pro-gift' => ['Sovg\'a', 'Подарок', 'Gift', 'shapes'],
        'pro-bookmark' => ['Xatcho\'p', 'Закладка', 'Bookmark', 'shapes'],
        'pro-diploma' => ['Diplom', 'Диплом', 'Diploma', 'shapes'],
        'pro-hand-heart' => ['Qo\'l va yurak', 'Рука с сердцем', 'Hand heart', 'shapes'],
        'pro-hand-shake' => ['Qo\'l berish', 'Рукопожатие', 'Handshake', 'shapes'],
        'pro-hand-stars' => ['Qo\'l va yulduz', 'Рука со звёздами', 'Hand stars', 'shapes'],
        'pro-plain' => ['Samolyot', 'Самолёт', 'Plane', 'shapes'],
        'pro-bus' => ['Avtobus', 'Автобус', 'Bus', 'shapes'],
        // Chiziqli ikonlar (lucide, ISC) — bitta rangli, konstruktorda rangi o'zgaradi
        'icon-globe' => ['Globus', 'Глобус', 'Globe (line)', 'icons'],
        'icon-flame' => ['Olov', 'Огонь', 'Flame', 'icons'],
        'icon-star' => ['Yulduz', 'Звезда', 'Star (line)', 'icons'],
        'icon-heart' => ['Yurak', 'Сердце', 'Heart (line)', 'icons'],
        'icon-zap' => ['Chaqmoq', 'Молния', 'Lightning (line)', 'icons'],
        'icon-crown' => ['Toj', 'Корона', 'Crown (line)', 'icons'],
        'icon-moon' => ['Oy', 'Луна', 'Moon', 'icons'],
        'icon-sun' => ['Quyosh', 'Солнце', 'Sun', 'icons'],
        'icon-cloud' => ['Bulut', 'Облако', 'Cloud', 'icons'],
        'icon-mountain' => ['Tog\'', 'Горы', 'Mountain (line)', 'icons'],
        'icon-music' => ['Musiqa', 'Музыка', 'Music', 'icons'],
        'icon-headphones' => ['Quloqchin', 'Наушники', 'Headphones', 'icons'],
        'icon-camera' => ['Kamera', 'Камера', 'Camera', 'icons'],
        'icon-coffee' => ['Qahva', 'Кофе', 'Coffee', 'icons'],
        'icon-rocket' => ['Raketa', 'Ракета', 'Rocket', 'icons'],
        'icon-smile' => ['Tabassum', 'Улыбка', 'Smile', 'icons'],
        'icon-eye' => ['Ko\'z', 'Глаз', 'Eye', 'icons'],
        'icon-anchor' => ['Langar', 'Якорь', 'Anchor', 'icons'],
        'icon-bike' => ['Velosiped', 'Велосипед', 'Bike', 'icons'],
        'icon-compass' => ['Kompas', 'Компас', 'Compass', 'icons'],
        'icon-feather' => ['Pat', 'Перо', 'Feather', 'icons'],
        'icon-gamepad-2' => ['Geympad', 'Геймпад', 'Gamepad', 'icons'],
        'icon-ghost' => ['Arvoh', 'Привидение', 'Ghost', 'icons'],
        'icon-leaf' => ['Barg', 'Лист', 'Leaf', 'icons'],
        'icon-map-pin' => ['Joylashuv', 'Локация', 'Map pin', 'icons'],
        'icon-palette' => ['Palitra', 'Палитра', 'Palette', 'icons'],
        'icon-paw-print' => ['Panja', 'Лапа', 'Paw', 'icons'],
        'icon-plane' => ['Samolyot', 'Самолёт', 'Plane', 'icons'],
        'icon-shield' => ['Qalqon', 'Щит', 'Shield', 'icons'],
        'icon-sparkles' => ['Uchqun', 'Искры', 'Sparkles', 'icons'],
        'icon-target' => ['Nishon', 'Мишень', 'Target', 'icons'],
        'icon-trophy' => ['Kubok', 'Кубок', 'Trophy', 'icons'],
        'icon-umbrella' => ['Soyabon', 'Зонт', 'Umbrella', 'icons'],
        'icon-wifi' => ['Wi-Fi', 'Wi-Fi', 'Wi-Fi', 'icons'],
        'icon-sword' => ['Qilich', 'Меч', 'Sword', 'icons'],
        'icon-infinity' => ['Cheksizlik', 'Бесконечность', 'Infinity', 'icons'],
    ];

    public function run(): void
    {
        $i = 0;
        foreach (self::ITEMS as $key => $item) {
            [$uz, $ru, $en, $category] = $item;
            $recolorable = $item[4] ?? true;
            $i++;
            if (Clipart::query()->where('name_en', $en)->exists()) {
                continue;
            }
            $source = database_path("seeders/cliparts/{$key}.svg");
            $path = "cliparts/{$key}.svg";
            Storage::disk('public')->put($path, file_get_contents($source));

            $file = File::query()->create([
                'disk' => 'public', 'path' => $path, 'original_name' => "{$key}.svg",
                'mime' => 'image/svg+xml', 'size' => filesize($source), 'width' => 100, 'height' => 100,
            ]);
            Clipart::query()->create(['name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en, 'category' => $category, 'file_id' => $file->id, 'recolorable' => $recolorable, 'sort' => $i]);
        }
    }
}
