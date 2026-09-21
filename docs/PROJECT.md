# Textile — CLAUDE.md

Logosiz sifatli kiyimlar (Xitoy / Turkiya) → foydalanuvchi o'z logosini qo'yib dizayn qiladi → razmer tanlaydi → buyurtma tikilib yetkaziladi.
Bu hujjat loyihaning **manba haqiqati**. Har bir feature/bugfix oldidan o'qi. O'zgarish kiritsang — shu yerni ham yangila.

---

## Tuzilma

```
textile/
├── api/      Laravel 13 (PHP 8.4) — REST API, Passport, Spatie Permission + QueryBuilder, Postgres
├── admin/    React 18 + TS + Vite + Ant Design — admin panel (makon/admin asosida)
├── web-3d/   MIJOZ SAYTI + 3D konstruktor (shirt-designer MIT asosida) — katalog, mahsulot, studio, buyurtmalar
└── (mobile/) Flutter — keyinroq. Client API tayyor: /api/v1
```

| Qism | Hujjat |
|------|--------|
| Backend qoidalari, domen, API kontrakt | [api/CLAUDE.md](api/CLAUDE.md) |
| Admin panel komponentlari, hooklar | [admin/CLAUDE.md](admin/CLAUDE.md) |
| Konstruktor (logo/yozuv kiyim ustida): tahlil, canvas JSON sxemasi, prototip | [docs/designer.md](docs/designer.md) |
| 3D konstruktor (web-3d) integratsiyasi | [web-3d/README.md](web-3d/README.md) |
| Serverga chiqarish (nginx, .env, yangilash) | [docs/deploy.md](docs/deploy.md) |

---

## Ishga tushirish (local)

```bash
# Backend (Postgres lokal: textile, test uchun textile_test)
cd api && composer install && cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed          # super-admin: +998901234567 / admin123
php artisan serve --port=8200             # 8100 band (makon/api)

# Admin
cd admin && npm install && npm run dev    # http://localhost:5173, API: .env.local → 127.0.0.1:8200

# 3D konstruktor
cd web-3d && npm install && npx vite --port 5174   # http://localhost:5174/?product=classic-tshirt
```

Passport password-grant client `.env` dagi `PASSPORT_PASSWORD_CLIENT_ID/SECRET` bilan seeder orqali deterministik yaratiladi — `migrate:fresh` dan keyin `.env` ni o'zgartirish shart emas.

---

## Domen (qisqa)

```
Category ─< Product ─< ProductColor (front/back/left/right mockup) ─< ProductVariant (size, sku, quantity, reserved)
                    └< PrintArea (front/back/sleeve: x,y,w,h % + max sm)
InventoryBatch (CN/TR, draft→received) ─< InventoryBatchItem → variant.quantity += qty
Design (user, product, product_color, canvas JSON v1 — docs/designer.md, preview, print_file)  ← FAQAT konstruktor
ReadyProduct (nom, narx, sizes[], rangi, qoldiq) ─< ReadyProductImage (file)  ← FAQAT admin kiritadi, konstruktorsiz
Review (user, product | ready_product | design, rating 1-5, comment, reply, status) — moderatsiyadan keyin saytda
Order (number, status, payment_*) ─< OrderItem (snapshot: nom, rang, razmer, narx, print_file)
       OrderItem qatori ikki xil: {product_variant_id, design_id?} (konstruktor) yoki {ready_product_id, size} (tayyor mahsulot)
                                  ─< OrderStatusHistory
StockMovement: in | out | reserve | release | adjust — har bir ombor harakati
GarmentModel (3D GLB fayl, zones — decal joylari, status) ─< Product.garment_model_id — konstruktor shu modelni yuklaydi
Banner, Page (slug, HTML 3 til), Setting (key-value, contact) — sayt CMS
Clipart (tayyor logo: bir rangli SVG recolorable yoki ko'p rangli; hozir UZ brend/klub logolari) — konstruktor galereyasi
PaymentTransaction (provider payme|click|uzum, state created|performed|cancelled) → Order.payment_status
PhraseTemplate (trend so'z: text, font, weight, style, fill, category) — konstruktor "Trend so'zlar"
```

**Ombor qoidasi:** `available = quantity - reserved`.
- Buyurtma yaratildi → `reserved += qty` (reserve)
- Bekor qilindi → `reserved -= qty` (release)
- Jo'natildi (`shipped`) → `quantity -= qty, reserved -= qty` (out)
- Partiya qabul qilindi → `quantity += qty` (in)

**Buyurtma holatlari (state machine, `App\Enums\OrderStatus`):**
`new → confirmed → printing → sewing → ready → shipped → delivered`, `cancelled` faqat `ready` dan oldin.
Noto'g'ri o'tish → 422. Mijoz faqat `new` ni bekor qila oladi.

---

## Auth va ruxsatlar

- **Passport password grant.** `POST /auth/login {login, password}` → `{data: {user, token, refresh_token, expires_in}}`.
  `login` = telefon (`+998..`, bo'sh joy/`+`siz ham bo'ladi — `App\Support\Phone::normalize`) yoki email.
- Refresh eski access tokenni bekor qiladi (rotatsiya). Admin axios interceptor buni o'zi qiladi.
- **Spatie Permission**, guard `api`. Permission nomi `<resurs>.<amal>`: `orders.update`, `inventory-batches.receive`.
  Ro'yxat: `api/database/seeders/PermissionSeeder.php` (`GROUPS`). Admin `useAccess('orders')` xuddi shu nomlarni tekshiradi.
- Rollar: `super-admin`, `manager`, `warehouse`, `operator`, `customer` (`RoleSeeder`).

---

## API javob formati (admin panel shunga bog'langan)

```
Bitta obyekt:  { data: {...} }
Ro'yxat:       { data: [...], current_page, per_page, total, from, to, last_page }
Xato:          { message } | 422: { message, errors: {field: [..]} }
```
`App\Support\ApiResponse` dan tashqari javob qaytarma.

So'rov parametrlari (Spatie QueryBuilder): `?include=a,b.c&filter[status]=active&sort=-id&per_page=50&page=2`.
Admin `useGet({params: {include, filter, sort, limit, page}})` → `limit` avtomatik `per_page` bo'ladi.

---

## Storage va xavfsizlik
- Yuklangan SVG serverda tozalanadi (`App\Support\SvgSanitizer`): script, on* atributlar, javascript:, tashqi href/src, foreignObject, animate; `<a>` ochiladi. Test: `tests/Unit/SvgSanitizerTest.php`.
- `/storage/*` javobida `Content-Security-Policy: default-src 'none'` va `nosniff` — SVG ichida skript qolsa ham bajarilmaydi. Prod nginx ham shu sarlavhalarni qo'ysin.
`/storage/{path}` Laravel route orqali CORS bilan beriladi (`routes/web.php`, `filesystems.local.serve=false`, symlink yo'q) — konstruktor boshqa origin'dan rasm/SVG o'qiydi. Prod nginx ham `Access-Control-Allow-Origin: *` qo'ysin.

## Kod qoidalari (ikkala qism uchun)

1. **Biznes logika Action'da** (`api/app/Actions`), controller — faqat request → action → response.
2. **N+1 taqiqlanadi.** Local'da `Model::shouldBeStrict()` yoqilgan — lazy load exception beradi. Har doim `with()/load()`.
3. **Pul** — `decimal(12,2)`, PHP da `bcadd/bcmul`, float emas.
4. **Ombor o'zgarishi** faqat tranzaksiya + `lockForUpdate()` bilan, id bo'yicha tartiblab (deadlock oldini olish). `StockMovement` yozuvi majburiy.
5. **Snapshot**: buyurtma qatoriga nom/rang/razmer/narx nusxalanadi — mahsulot keyin o'zgarsa tarix buzilmaydi.
6. Enum'lar `api/app/Enums` ↔ `admin/src/services/constants.ts` — ikkalasini birga yangila.
7. Yangi resurs qo'shish tartibi: migration → model (+relation generics) → resource → request → controller → route (`permission:` bilan) → `PermissionSeeder::GROUPS` → test → admin sahifa → `routes/index.tsx`.
8. Tekshiruv (push oldidan): `cd api && vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test`; `cd admin && npx tsc -b && npm run build`.

---

## Hozirgi holat / keyingi qadamlar

- [x] Backend: auth, users/roles/permissions, katalog, ombor (partiya + qoldiq + tuzatish), dizayn, buyurtma (state machine), dashboard, 19 feature test
- [x] Admin: barcha yuqoridagi sahifalar, dizaynni mockup ustida ko'rsatish + buyurtmada "qayerda nima"
- [x] 5 ta 3D model bazada (futbolka, polo, uzun yeng, xudi, triko) — Draco bilan siqilgan, 0.5–2.4 MB; web-3d/README.md
- [x] Admin → Katalog → 3D modellar: GLB yuklash (60 MB), faol/faol emas, zonalar JSON, muallif; mahsulot formasida tanlanadi
- [x] Admin: buyurtma/dizayn sahifasida 3D ko'rinish (web-3d embed, `VITE_DESIGNER_URL`)
- [x] 3D konstruktor (web-3d): shirt-designer + API (mahsulot/rang/variant, shriftlar, trend so'zlar, tayyor logolar, kirish, saqlash, buyurtma), canvas v2
- [x] Konstruktor (2D prototip): canvas JSON sxemasi + validatsiya, shriftlar, tayyor logolar (bir/ko'p rangli), trend so'zlar (25 seed), 3 kiyim turi vektor mockup (futbolka/polo/uzun yeng), 4 ko'rinish aylantirish, rang → mockup/tint, Fabric.js prototip (`/designer/`)
- [x] CMS: bannerlar, statik sahifalar (CKEditor, 3 til), aloqa sozlamalari — admin → Sayt; `GET /site`, `/pages/{slug}`
- [x] Sayt tili uz/ru/en: UI `web-3d/src/i18n.ts`, kontent `Accept-Language`; konstruktor faqat kirganlarga
- [x] Admin dizaynni konstruktorda tahrirlaydi: dizayn sahifasi → "Konstruktorda tahrirlash" → `web-3d/studio?design=ID#token=` → `PUT /admin/designs/{id}` (buyurtma bosma fayli ham yangilanadi)
- [x] Mijoz sayti (web-3d): bosh sahifa (tayyor dizaynlar + kiyimlar, 3D thumbnaillar), mahsulot, konstruktor (`?template=ID`), buyurtmalarim, profil, footer
- [x] **Ikki oqim aralashmaydi**: konstruktor (3D, dizayn, variant/ombor) va tayyor mahsulot (admin kiritgan foto, razmer ro'yxati) — buyurtma ikkalasidan ham keladi
- [x] Tayyor mahsulot: `ready_products` + `ready_product_images`, admin CRUD (Katalog → Tayyor mahsulotlar, ko'p rasm yuklash), sayt `/ready` va `/ready/:slug`
- [x] Tayyor mahsulot sahifasi marketplace ko'rinishida: kichik rasmlar ustuni + katta galereya (o'q tugmalar), yurakcha va ulashish, reyting va sotilgan soni, chegirma foizi, `specs` xususiyatlar jadvali, razmer, soni, "Savatga qo'shish" / "Hozir sotib olish"
- [x] Savat sahifasi: mahsulotlar soni, savatni tozalash, "Buyurtmangiz" paneli (mahsulotlar / yetkazish / umumiy narx), ishonch belgilari qatori
- [x] Shaxsiy kabinet (Uzum uslubi): chapda menyu (`web-3d/src/shop/AccountLayout.tsx`), buyurtmalar kartochkalari (holat chipi, kichik rasmlar), `Ma'lumotlarim` (ism/familiya/email tahriri, `PUT /auth/profile`)
- [x] Manzillar kitobi: `user_addresses` jadvali + `GET/POST/PUT/DELETE /addresses`, sayt `/addresses` sahifasi (asosiy manzil, tahrir, o'chirish); checkout'da saqlangan manzildan tanlash (`AddressPicker`)
- [x] Sahifa almashganda tepaga qaytadi (`web-3d/src/shop/ScrollToTop.tsx`), "orqaga" bosilganda avvalgi joy saqlanadi; yuklanish paytida skelet ko'rsatiladi (sahifa sakramaydi)
- [x] Ko'p rasmli mahsulot: kartochkada ikkinchi rasm hover'da + rasm soni belgisi, sahifada kichik rasmlar ustuni va to'liq ekran (lightbox) ko'rinish
- [x] Rang tanlash brauzerning eski OS oynasi o'rniga o'z komponentimiz (`web-3d/src/components/editor/ColorField.tsx`): hex maydon + tayyor ranglar
- [x] Kirish oynasi Uzum uslubida: avval telefon (`POST /auth/check` raqam bazada bormi), keyin parol yoki ro'yxatdan o'tish; raqam maskasi, parolni ko'rsatish, orqaga qaytish
- [x] Til almashish sahifani qayta yuklamaydi (`window.location.reload()` olib tashlandi): `setLang` faqat holatni yangilaydi va kontentni yangi tilda qayta oladi, `<Routes key={lang}>` sahifani qayta chizadi
- [x] Banner sliderida ikki yon tomonda o'q tugmalari + nuqtalar
- [x] Til tanlash: bayroq + kod tugmasi, ochiladigan ro'yxat (SVG bayroqlar, belgilangan til ✓) — `web-3d/src/shop/LangSwitcher.tsx`
- [x] Bosh sahifa banneri: 3D embed o'rniga katta rasm (admin yuklaydi; bo'lmasa tayyor mahsulot fotolaridan kollaj) + qisqa matn
- [x] Savat va sevimlilar: `web-3d/src/store/cartStore.ts` (localStorage `tx_cart`), header'da hisoblagichlar, `/cart` (bir nechta mahsulot → bitta buyurtma) va `/favorites`; kartochkada yurakcha va savat tugmasi
- [x] Konstruktor interfeysida emoji ikonlar o'rniga lucide ikonlari (zona tanlash, yorug'lik presetlari)
- [x] To'lov majburiy: naqd olib tashlandi, buyurtma yaratilgach darhol Payme/Click/Uzum sahifasiga o'tadi
- [x] Konstruktor galereyasi: brend/klub logolari o'chirildi, o'rniga 136 ta professional ikon (Solar CC BY 4.0 + lucide ISC)
- [x] Tayyor dizaynlar: `designs.is_template` (admin dizayn sahifasida tugma), `GET /templates`; 6 ta brendli seed (Paxtakor, Bunyodkor, gerb, bayroq, Nasaf, MOQ)
- [x] Tayyor dizaynni birdan sotib olish: `/design/:id` (rasm, razmer, soni, to'lov, "Sotib olish" / "O'zgartirish"); `CreateOrder` shablonni xaridor uchun nusxalaydi (`is_template` dizaynlar)
- [x] Kepka 3D modeli (Scott VanArsdale, CC-BY, 0.95 MB), `cap-classic`, razmer UNI, 5 rang, old panel bosma zonasi; 3 ta kepka shabloni
- [x] Merch kolleksiyasi (tirikchilik uslubi): 16 ta shablon (minimal bitta so'z / oval belgi + shior), merch belgilari (oval, smiley, globus...), dumaloq displey shriftlar (Lilita One, Fredoka, Baloo 2, Rubik Mono One, Titan One, Chewy), ranglar: to'q ko'k #1B2A4A, krem #F1EDE4
- [x] 3D zonalar nisbiy: `garment_models.zones = {front:{rel:[dx,dy],scale}}` (shim: son qismi)
- [x] Logolar: 32 ta O'zbekiston brend/klub logosi (Wikimedia) — `UzLogoSeeder`; eski cliparts o'chirildi. Tovar belgilari — huquqiy javobgarlik mijozda
- [x] Mahsulot turi: `products.type` blank | finished
- [x] `/tools/thumbs?auto=1` (admin sifatida) — 3D thumbnail va shablon preview'larini avtomatik yaratadi
- [x] Do'kon fotosi: tayyor mahsulot kartochkalari 3D render emas, haqiqiy studiya fotosi maketi (`web-3d/src/utils/photoMockup.ts`, plastinkalar `web-3d/public/mockups/`), 4:5 kartochka; `/tools/thumbs?auto=1&only=photos`
- [x] Sayt dizayni yorug' (marketplace uslubi): oq header, kulrang fon, oq kartochkalar, to'q ko'k footer. Konstruktor (`/studio`) qorong'i qoladi, saytdagi 3D embed yorug'
- [x] Tayyor mahsulotlar uchun tekis (flat) kiyim maketi: `web-3d/public/mockups/tee-flat.svg` (`__BODY__` rangga almashadi) + `photoMockup.ts` — 3D render o'rniga marketplace uslubidagi rasm
- [!] **DEMO KONTENT**: `DemoMerchSeeder` — 50 ta shablon rasmi va nomi tirikchilik.uz dan namuna sifatida olingan (`storage/app/public/demo/tirikchilik`, `database/seeders/data/tirikchilik-demo.json`). Bu bizning mulkimiz emas, faqat MVP ko'rsatish uchun. **Ishga tushirishdan oldin o'z fotolarimiz bilan almashtirilishi shart**; o'chirish: `Database\Seeders\DemoMerchSeeder::purge()`
- [ ] Qolgan kiyimlar uchun flat maket (polo, uzun yeng, xudi, triko, kepka) — hozircha ular 3D renderda
- [x] 3D katalog kartochkasi bosilsa to'g'ridan-to'g'ri konstruktor ochiladi (`/studio?product=`); mahsulot sahifasida vektor mockup o'rniga 3D
- [x] Konstruktor ham sayt bilan bir xil yorug' temada (fon, panellar, modallar); 3D sahna foni oq-kulrang, ContactShadows yumshatilgan
- [x] Ikonlar kutubxonasi: 36 ta chiziqli ikon (lucide, ISC) — `icons` kategoriyasi, bitta rangli, konstruktorda rangi o'zgaradi
- [x] To'lov usullari bitta komponentda (`web-3d/src/shop/PayPicker.tsx`): faqat rasmiy logotiplar, ostida yozuv yo'q; Click logotipi `public/pay/click.svg` (oq matnli) va `click-dark.svg` (oq plitka uchun)
- [x] Soni uchun `QtyInput` (− / +) — brauzer strelkalari o'rniga; sayt va konstruktor modalida bir xil
- [x] Sharhlar (review): `reviews` jadvali, mijoz yozadi (faqat buyurtma qilgan mahsulotga, 1 marta), admin tasdiqlaydi/javob yozadi, reyting mahsulot va shablon resurslarida (`rating`, `reviews_count`)
- [ ] SEO (SSR) kerak bo'lsa keyin Next.js; DPI ogohlantirish; to'lov
- [x] To'lov: Payme Merchant API (JSON-RPC) + Click SHOP-API callback'lari, Uzum havola; `payment_transactions`; sayt checkout'da usul tanlash → redirect → `/payment/result`. Prod uchun `.env`: PAYME_MERCHANT_ID/KEY, CLICK_*, UZUM_SERVICE_ID (shartnoma kalitlari)
- [ ] SMS OTP (client register/login tasdiqlash) — `users.phone_verified_at` mavjud
- [ ] Mobile (Flutter): katalog/buyurtma nativ (`/api/v1`), konstruktor WebView (`web-3d /studio`)
- [ ] Bildirishnomalar (holat o'zgarganda mijozga SMS/push)
- [ ] phpstan darajasini 3 → 6 ga ko'tarish
