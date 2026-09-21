# Konstruktor (dizayner) — texnik tahlil va qaror

Mijoz kiyim ustiga logo yoki yozuv qo'yadi, sichqoncha bilan suradi, kattalashtiradi, buradi, shriftini tanlaydi va shu holatda buyurtma beradi. Admin buyurtmani ochganda **qayerda, qaysi logo/so'z, qaysi shrift, necha sm** ekanini ko'radi.

---

## 1. "3D" masalasi: nima tanladik va nega

| Yondashuv | Qanday ishlaydi | Afzallik | Kamchilik | Kim ishlatadi |
|---|---|---|---|---|
| **A. 2D mockup + overlay** | Har rang uchun old/orqa foto, ustiga canvas (Fabric.js). Element `multiply` blend bilan chiziladi — matoga "singib" ko'rinadi | Tez, arzon, har qanday brauzer/telefonda ishlaydi, mockup = oddiy foto | Buralgan mato deformatsiyasi yo'q (tekis ko'rinadi) | Printful (asosiy), Teespring, ko'pchilik UZ/RU servislar |
| **B. 2D + displacement map** | A + har mockup uchun burma xaritasi (grayscale PNG), print WebGL shader orqali "egiladi" | Deyarli fotorealistik, arzon | Har mockup uchun displacement map tayyorlash kerak (Photoshop yoki 1 marta avtomat) | Printful "realistic preview", Placeit |
| **C. Haqiqiy 3D (three.js)** | GLB modeli (futbolka), print tekstura sifatida UV ga qo'yiladi, 360° aylantiriladi | "Vau" effekt, aylantirish | Har mahsulot turi uchun 3D model + UV ($$$), telefonlarda og'ir, matn/rang aniq chiqishi qiyin, ishlab chiqarish uchun baribir 2D fayl kerak | Nike By You, kichik startaplar demo uchun |

**Qaror: A hozir, B keyin (ixtiyoriy), C emas.**
Sabab: bosma fabrika baribir tekis 2D fayl (PNG/PDF, sm o'lchamda) oladi. Ma'lumot modeli (`canvas JSON`) uchala yondashuvda bir xil — keyin B yoki C qo'shish faqat *ko'rsatish* qatlamini almashtiradi, backend va admin o'zgarmaydi.

"3D his" berish uchun A da qilinadi: sifatli foto mockup (haqiqiy futbolka, biroz burchakli), `multiply` blend, engil soya, old/orqa/yeng tomonlarini almashtirish animatsiyasi.

---

## 2. Ma'lumot modeli — hamma narsa shu JSON atrofida

```jsonc
{
  "version": 1,
  "sides": {
    "front": { "layers": [
      { "id": "f0", "type": "text",  "print_area_id": 1,
        "x": 5, "y": 28, "width": 90, "height": 44, "rotation": 0,        // PrintArea ichida FOIZ
        "text": "SALOM", "font_family": "Lobster", "font_weight": "400", "font_style": "italic",
        "fill": "#111111", "align": "center", "letter_spacing": 0 },
      { "id": "f1", "type": "image", "print_area_id": 2,
        "x": 20, "y": 10, "width": 60, "height": 60, "rotation": -5, "file_id": 17 }
    ]},
    "back": { "layers": [] }
  }
}
```

Nega foiz, piksel emas: sayt 640px canvas, mobil 360px, admin 420px, bosma fayl 3000px — hammasi bitta formuladan chizadi:

```
absX = area.x + layer.x * area.width / 100       (mockup foizida)
width_cm = layer.width * area.max_width_cm / 100  (fizik o'lcham)
```

`PrintArea` (admin → Mahsulot → Bosma joylari): tomon, mockupdagi joy (x,y,w,h %), maksimal fizik o'lcham (sm). Element shu to'rtburchakdan chiqolmaydi (clip + clamp). Bu ishlab chiqarish cheklovi: pressning o'lchami, yoqa/tikuv joylari.

Validatsiya: `App\Support\DesignCanvas::rules()` — tomon, joy, shrift (`config/fonts.php`), koordinatalar 0..100, matn ≤120 belgi, ≤20 qatlam/tomon. Xato → 422 (`canvas.sides.front.layers.0.font_family`).

---

## 3. Shriftlar

- Ro'yxat: `api/config/fonts.php` → `GET /api/v1/fonts`. Google Fonts (bepul, tijorat uchun ruxsat).
- Har shrift `styles`: `400`, `700`, `400i` (italic). Konstruktorda B / I tugmalari faqat mavjud variantlarni yoqadi.
- Kategoriyalar: sans, serif, display, script, handwriting ("qo'lda yozilgan" — Caveat, Permanent Marker, Dancing Script).
- Canvas'da chizishdan oldin `document.fonts.load()` — aks holda fallback shrift bilan chiziladi va preview noto'g'ri bo'ladi.
- Bosma fayl server tomonda generatsiya qilinsa, shu shriftlarning TTF'lari serverda bo'lishi kerak (`storage/fonts/`).

---

## 4. Oqim

```
Sayt/Mobil                          API                              Admin
─────────                           ───                              ─────
GET /products/{slug}  ─────────►  mockuplar + print_areas + variantlar
GET /fonts
[logo yuklash] POST /files ──────►  files (PNG/SVG, ≤20MB)
[canvas tahrir: Fabric.js]
preview PNG → POST /files
POST /designs {canvas, preview} ─►  DesignCanvas validatsiya → Design(ready emas, draft)
POST /orders {variant, design_id} ► CreateOrder: ombor band, narx snapshot,
                                     OrderItem.print_file_id, Design → ready
                                                                   ── GET /orders/{id}: items.design.summary
                                                                      "Old · Ko'krak chap: "SALOM" — Lobster 400 italic (10.0 × 4.4 sm)"
                                                                   ── GET /designs/{id}: DesignPreview (SVG overlay mockup ustida)
                                                                      + jadval: tomon / joy / tur / nima / sm
```

---

## 5. Bosma fayl (print-ready)

Hozir: preview PNG (640px, mockup bilan) saqlanadi — operator ko'rish uchun yetarli.
Keyingi qadam (ishlab chiqarish uchun): har tomon, har bosma joyi uchun **shaffof PNG, 300 DPI**:
- o'lcham px = `max_width_cm / 2.54 * 300` (28 sm → 3307px)
- qatlamlar foizdan px ga o'tkaziladi, matn TTF bilan render (server: PHP Imagick yoki Node `canvas`/`sharp`; tavsiya — Node worker, chunki Fabric.js'ning o'zi `node-canvas` bilan serverda ishlaydi va brauzer bilan 1:1 natija beradi)
- natija `designs.print_file_id` (+ har joy uchun alohida fayl kerak bo'lsa `design_print_files` jadvali)
- queue: `GeneratePrintFile` job, dizayn `ready` bo'lganda

Mijoz yuklagan logo sifati: `files.width/height` bor → `width_cm` ga nisbatan DPI hisoblanadi; 150 DPI dan past bo'lsa konstruktorda ogohlantirish ("Logo sifati past, bosmada xira chiqadi").

---

## 6. Sayt (production) uchun stek

- **Next.js + Fabric.js** (prototip kodi to'g'ridan-to'g'ri React komponentga ko'chadi: `state`, `exportCanvas`, `clamp` — o'zgarmaydi).
- Mobil (Flutter): `flutter_canvas`/`CustomPainter` yoki WebView ichida shu konstruktor. WebView tavsiya — bitta kod, bir xil natija.
- Undo/redo: Fabric `canvas.toJSON()` stack.
- Avto-saqlash: 5 s debounce → `PUT /designs/{id}` (status draft).
- Narx: `base_price + (qatlam bor tomon soni × print_price)` — hozir `print_price` bitta; tomon soniga qarab bo'lsa `products.print_price_back` qo'shish mumkin.

---

## 7. Ko'rinishlar, ranglar, tayyor logolar

**4 ko'rinish (aylantirish).** `product_colors` da har rang uchun `front/back/left/right_image_id`. Konstruktorda ◀ ▶ bilan old → o'ng yeng → orqa → chap yeng aylanadi. Bosma joyi (`PrintArea.side`) faqat kerakli tomonlarga qo'yiladi — qolganlari faqat ko'rish uchun.

**Rang almashganda mockup almashadi.** Tartib: (1) shu rangning o'z fotosi bor — o'sha; (2) yo'q — boshqa rangning fotosi olinib `multiply` bilan rang HEX'iga bo'yaladi (tint). Demak minimal talab: **bitta oq futbolkaning 4 fotosi** (oq fonsiz/PNG) — qolgan ranglar avtomatik. Sifat uchun har rangni alohida suratga olish yaxshiroq (qora mato tint bilan "yassi" chiqadi).

Foto talablari: kvadrat (masalan 1500×1500), shaffof yoki oq fon, kiyim markazda, har tomon uchun bir xil masshtab (bosma joyi foizlari to'g'ri tushishi uchun). Rasmlardagi kabi "3D" ko'rinish — bu shunchaki sifatli studiya fotosi.

**Tayyor logolar (`cliparts`).** Admin → Katalog → Tayyor logolar: bir rangli SVG (shaffof fon) yuklanadi, kategoriya, "rangi o'zgartiriladi" bayrog'i. Konstruktorda galereya, bosilsa bosma joyiga tushadi, palitradan rang tanlanadi (SVG `fill`). Canvas'da `type: "clipart", clipart_id, fill`. Ko'p rangli logolar uchun `recolorable=false` — rang o'zgarmaydi. Admin dizayn ko'rinishida SVG alpha-mask orqali xuddi shu rangda chiziladi.

**Matn rangi.** Qora/to'q matoda default oq, ochda qora; `multiply` faqat och matoda (qora matoda oq bosma ko'rinmay qolmasligi uchun `source-over`).

## 8. Kontent: trend so'zlar, rangli logolar, kiyim turlari

**Trend so'zlar (`phrase_templates`).** Admin → Katalog → Trend so'zlar: matn + shrift + og'irlik + uslub + rang. Konstruktorda "Trend so'zlar" paneli, bir bosishda tayyor uslubda tushadi va **tahrirlanadi** (oddiy matn qatlami, canvas'da `type: text`). 25 ta start so'z seed qilingan (motivatsiya, hazil, O'zbekiston, sevgi, sport). Eskirganini "Faol emas" qilinadi, yangisi qo'shiladi — kod o'zgarmaydi. Tavsiya: har oy 5–10 ta yangi, TikTok/Instagram UZ trendlaridan; kontent-menejer vazifasi.

**Rangli logolar.** `cliparts.recolorable=false` — ko'p rangli SVG yoki PNG, rangi o'zgarmaydi, rasm sifatida tushadi (nishon, O'zbekiston bayrog'i, quyosh seed qilingan). `recolorable=true` — bir rangli SVG, mijoz rang tanlaydi. Ikkalasi ham bosma faylga to'g'ridan-to'g'ri o'tadi. Litsenziya: faqat o'zimizniki yoki CC0/tijoriy ruxsatli logolar yuklansin (brend logotiplari — huquqiy xavf).

**Kiyim turlari.** Mockup mahsulot rangiga bog'langan, shuning uchun har qanday kiyim (futbolka, polo/yoqali, uzun yengli, xudi, kepka) — bu shunchaki boshqa 4 ta rasm + o'z bosma joylari. `MockupSeeder` 3 turni yaratadi (futbolka, polo, uzun yengli) × 6 rang × 4 ko'rinish — vektor, soya/burma/mato teksturasi bilan. Haqiqiy foto yuklansa seeder unga tegmaydi (`mockups/` yo'lidagi faqat o'zi yaratganini yangilaydi).

**"Jonli" ko'rinish uchun yakuniy yechim** — baribir studiya fotosi (4 ko'rinish, shaffof fon, 1500×1500+). Vektor mockup — foto kelguncha vaqtinchalik. Fotolarni yuklash: Admin → Mahsulotlar → mahsulot → Ranglar → tahrirlash (old/orqa/chap/o'ng).

## 9. Nima tayyor (kod)

| Qism | Fayl |
|---|---|
| Canvas sxemasi + validatsiya + xulosa | `api/app/Support/DesignCanvas.php`, `Requests/Client/Designs/StoreDesignRequest.php` |
| Shriftlar | `api/config/fonts.php`, `GET /api/v1/fonts` |
| Admin ko'rinish (mockup ustida SVG) | `admin/src/components/design-preview/index.tsx`, `pages/designs/view.tsx` |
| Buyurtmada "qayerda nima" | `DesignResource.summary`, `pages/orders/view.tsx` |
| Ishlaydigan prototip | `api/public/designer/index.html` → http://127.0.0.1:8200/designer/ |
| Trend so'zlar | `PhraseTemplate`, `Admin/PhraseTemplateController`, `GET /api/v1/phrases`, `PhraseTemplateSeeder` (25), admin `/phrases` |
| Vektor mockuplar | `database/seeders/mockups/*.svg` (3 tur × 4 ko'rinish, `{{COLOR}}`), `MockupSeeder` |
| Tayyor logolar | `api/app/Models/Clipart.php`, `Admin/ClipartController`, `GET /api/v1/cliparts`, `ClipartSeeder` (8 ta SVG), admin `/cliparts` |
| 4 ko'rinish | `product_colors.left/right_image_id`, prototip ◀ ▶, admin rang modalida yon rasmlar |
| Testlar | `tests/Feature/Client/DesignCanvasTest.php` (4), `OrderFlowTest` yangilandi |

Prototipda bor: rang tanlash (mockup almashadi/tint), 4 ko'rinishni aylantirish, tayyor logolar + rang palitrasi, yozuv (14 shrift, bold/italic, rang, harf oralig'i, tekislash), logo yuklash, surish/kattalashtirish/burish, bosma joyidan chiqmaslik, sm o'lchami ko'rsatkichi, saqlash, buyurtma.
Yo'q (production'da qo'shiladi): undo/redo, avto-saqlash, DPI ogohlantirish, ko'p element tanlash, mobil touch (Fabric touch'ni qo'llaydi, faqat UI moslash), 300 DPI print fayl.

---

## 10. Jahon bozori: tayyor yechimlar (2026-09 tahlili)

| Yechim | Turi | Narx | Bizga mos keladimi |
|---|---|---|---|
| **Zakeke** (zakeke.com) | SaaS, iframe + API, haqiqiy WebGL 3D + AR | Starter ~$68–80/oy, Grow ~$150–170, Scale ~$340 + har buyurtmaga fee, 3D/AR alohida add-on, mahsulot limiti (5/25/50) | Custom saytga API orqali ulanadi. Eng chiroyli 3D. Kamchilik: doimiy to'lov + fee, mahsulot limiti, ma'lumot ularda, UZ to'lov/til bizniki emas |
| **Kickflip** (gokickflip.com) | SaaS, 2D yuqori sifat render | $59/oy dan; custom sayt faqat Enterprise | Custom stack uchun qimmat, 3D yo'q |
| **Customily / Teeinblue / Podifai** | Shopify ilovalari | $49/oy + har sotuvga fee | Shopify'siz ishlamaydi — mos emas |
| **Printful Embedded Design Maker** | Bepul JS embed | Bepul, lekin ishlab chiqarish Printful'da | Biz o'zimiz tikamiz — mos emas |
| **Fancy Product Designer (JS)** | Fabric.js asosidagi 2D konstruktor, bir martalik litsenziya | ~$200–350 bir martalik (sayt bo'yicha) | Bizning prototipga eng yaqin; sotib olsak vaqt tejaydi, lekin sxema ularniki bo'ladi |
| **Dynamic Mockups API** | PSD mockup → fotorealistik render, <1 s | 50 bepul kredit, keyin kreditli Pro | **Eng foydali qo'shimcha**: bizning canvas → ularning PSD shabloni → haqiqiy fotoga tushgan futbolka. O'z konstruktorimiz qoladi |
| **shirt-designer** (GitHub, MIT) | three.js 3D, GLB model, PSD 300 DPI eksport | Bepul | 1 yulduz, yetilmagan; lekin GLB + R3F yondashuvi bizga 3D ko'rinish qo'shishda namuna |

**Xulosa.** To'liq tayyor konstruktorni sotib olish (Zakeke) — tez "vau", lekin oylik to'lov, har buyurtmaga fee, mahsulot limiti va sayt/mobil/ombor/buyurtma bilan integratsiya baribir biz tomonda. Bozordagi barcha yirik POD (Printful, Printify) o'z konstruktorini o'zi yozgan — bu loyihaning yuragi bo'lgani uchun biz ham shunday qilamiz (allaqachon 70% tayyor).

Chiroyli ko'rinish uchun ikkita yo'l, ikkalasi ham bizning canvas JSON bilan ishlaydi:
1. **Foto mockup + Dynamic Mockups API** (yoki o'zimiz PSD/displacement render) — real fotoga tushgan futbolka, arzon, 1–2 kun.
2. **3D GLB + three.js** (shirt-designer kabi) — 360° aylantirish, har kiyim turi uchun model (Sketchfab/CGTrader $10–50 yoki buyurtma), 1–2 hafta.

Tavsiya: 1-yo'l darhol, 2-yo'l sayt chiqqandan keyin.


---

## 11. Qaror: shirt-designer (3D) asos qilib olindi

`web-3d/` — MIT loyiha to'liq, o'z imkoniyatlari bilan (360°, PSD eksport, arka matn, shakllar, palitra). Biz faqat qo'shdik: mahsulot/rang/ombor, shrift va trend so'zlar API'dan, tayyor logolar, kirish, saqlash (canvas v2) va buyurtma. 2D prototip (`api/public/designer/`) qoladi — yengil fallback va admin overlay uchun.
Batafsil: [../web-3d/README.md](../web-3d/README.md).
