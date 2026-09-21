# Textile API — CLAUDE.md

Laravel 13 · PHP 8.4 · PostgreSQL · Passport 13 · spatie/laravel-permission 8 · spatie/laravel-query-builder 7.
Umumiy qoidalar: [../CLAUDE.md](../CLAUDE.md). Bu fayl — backend tafsilotlari.

---

## Qatlamlar

```
routes/api/admin.php      /api/v1/admin/*  — har route `permission:<resurs>.<amal>` bilan
routes/api/client.php     /api/v1/*        — sayt va mobil (auth:api, egalik tekshiruvi controller'da)
app/Http/Controllers/Admin|Client   yupqa: request → (QueryBuilder | Action) → ApiResponse
app/Http/Requests         validatsiya. Admin so'rovlar ApiFormRequest dan meros: POST=required, PUT=sometimes|required
app/Http/Resources        javob shakli. Relation faqat whenLoaded() — hech qachon lazy load emas
app/Actions               biznes operatsiyalar (tranzaksiya, lock, StockMovement)
app/Enums                 holatlar (string-backed). OrderStatus::transitions() — state machine
app/Support               ApiResponse, Phone, SequenceNumber, Translatable
app/Services/Auth         PassportTokenService — /oauth/token ga ichki so'rov (client secret backendda)
```

### Action'lar
| Action | Nima qiladi |
|--------|-------------|
| `Inventory\SyncProductVariants` | Mahsulotga rang qo'shilganda faol razmerlar bo'yicha SKU/variant yaratadi (mavjudini saqlaydi) |
| `Inventory\ReceiveInventoryBatch` | draft → received; variantlarni `lockForUpdate`, `quantity += qty`, `StockMovement(in)` |
| `Orders\CreateOrder` | ombor tekshiruvi, `reserved += qty`, narx snapshot (bcmath), raqam, tarix, dizayn → ready |
| `Orders\ChangeOrderStatus` | o'tishni tekshiradi; cancelled → release, shipped → out; timestamp + tarix |

### QueryBuilder shabloni
```php
$items = QueryBuilder::for(Order::query()->with('user'))     // eager load — for() ichida
    ->allowedIncludes('items')                                // variadic! massiv emas
    ->allowedFilters(AllowedFilter::exact('status'), AllowedFilter::callback('q', fn ($q, $v) => ...))
    ->allowedSorts('id', 'total')
    ->defaultSort('-id')
    ->withCount('items')
    ->paginate($this->perPage($request));                     // ?per_page (max 200)

return ApiResponse::paginated($items, OrderResource::class);
```
`Model::shouldBeStrict()` local'da yoqiq: qisman `select` (`product:id,name`) resource'da MissingAttributeException beradi — to'liq relation yukla.

---

## Auth

- `POST admin/auth/login` → `PassportTokenService::issueByPassword()` → `app()->handle(Request::create('/oauth/token'))`.
  Ichki so'rov to'liq kernel orqali o'tadi (Passport `ServerRequestInterface` ni konteynerdagi `request` dan oladi), keyin asl request tiklanadi.
- `User::findForPassport()` — telefon yoki email, faqat `status=active`.
- Token TTL: `PASSPORT_TOKEN_TTL_HOURS` (12), refresh `PASSPORT_REFRESH_TTL_DAYS` (30).
- `get-me` → `UserResource` + `permissions: [...]` (admin `useAccess` uchun). `roles.permissions` include qilinganda hisoblanadi.
- Muddati o'tgan token → 401, `OAuthServerException` log qilinmaydi (`dontReport`).

---

## Ruxsatlar

`PermissionSeeder::GROUPS` — yagona ro'yxat. Yangi resurs qo'shsang:
1. `GROUPS` ga qo'sh → `php artisan db:seed --class=PermissionSeeder`
2. `RoleSeeder` da kerakli rollarga bering (super-admin hammasini avtomatik oladi)
3. Route'da `->middleware('permission:<resurs>.<amal>')`
4. Admin `useAccess('<resurs>')` shu nomni o'qiydi

---

## Endpointlar (qisqa)

**Admin `/api/v1/admin`**
```
POST  auth/login | auth/refresh | auth/logout      GET get-me
POST  translations/{locale}  {message}             (i18next backend; kalit yo'q bo'lsa qo'shiladi)
POST  files  (file | files[])                      → { data: [FileResource] }
GET   dashboard
CRUD  users, roles (+ GET/POST roles/{id}/permissions), permissions
CRUD  ready-products (tayyor mahsulot: rasm, razmer, narx — konstruktorsiz)
CRUD  categories, colors, sizes, products          PUT {resource}/sort {ids}
      products/{id}/colors, products/{id}/print-areas
GET   variants  (filter: product_id, sku, in_stock, low_stock=N)   POST variants/{id}/adjust {quantity:±n}
CRUD  inventory-batches                             POST inventory-batches/{id}/receive
GET   designs, designs/{id}
GET   reviews (filter: status, product_id, rating)   PUT reviews/{id} {status, reply}   DELETE reviews/{id}
GET   orders, orders/{id}   POST orders/{id}/status {status, comment}   POST orders/{id}/payment
```
**Client `/api/v1`**
```
POST auth/register | login | refresh | logout   GET auth/me
GET  categories | products (filter: category_id, gender, search, price_from/to) | products/{slug}
GET  ready-products | ready-products/{slug}
CRUD addresses (o'ziniki)    PUT auth/profile {first_name,last_name,email}
GET  reviews (filter[product_id|ready_product_id|design_id]) | reviews/summary   POST reviews (auth, sotib olgan bo'lsa)
POST files      CRUD designs (faqat o'ziniki)     GET/POST orders, GET orders/{id}, POST orders/{id}/cancel
```
Til: `?lang=ru` yoki `Accept-Language` → `name`/`description` shu tilda (`Translatable::translated`).

---

## To'lovlar
- `config/payments.php` + `.env`. Havola: `PaymentLinks::for(order, method)` → `GET /orders/{id}/pay-url?provider=`.
- Payme: `POST /payments/payme` (JSON-RPC, Basic `Paycom:{PAYME_KEY|PAYME_TEST_KEY}`) — `PaymeMerchantService` (CheckPerform, Create, Perform, Cancel, Check, GetStatement). Summa tiyinda.
- Click: `POST /payments/click/prepare|complete` — `ClickShopService` (md5 imzo). Prod'da Click kabinetida shu URL'lar ko'rsatiladi.
- Uzum: faqat redirect havola; callback shartnomadan keyin qo'shiladi.
- Test: `tests/Feature/PaymentsTest.php`.

## Ma'lumotlar bazasi

- Migratsiyalar: `2026_09_21_1000xx_*` — files/translations, catalog, inventory, designs+orders.
- Raqamlar: `SequenceNumber::next()` — Postgres `pg_advisory_xact_lock`, tranzaksiya ichida chaqirilsin.
- Indekslar: `orders(status, created_at)`, `product_variants(product_id, quantity)`, `stock_movements(variant, created_at)`.
- Test bazasi: `textile_test` (phpunit.xml), `RefreshDatabase`. SQLite ishlatma — `ilike` va advisory lock Postgres'ga bog'liq.

---

## Buyruqlar

```bash
php artisan serve --port=8200
php artisan migrate:fresh --seed
php artisan test                         # 14 feature test
vendor/bin/pint                          # format
vendor/bin/phpstan analyse               # level 3 (phpstan.neon), 0 xato bo'lishi shart
php artisan passport:keys                # prod: kalitlar yo'q bo'lsa
```

## Seed ma'lumotlar
super-admin `+998901234567 / admin123`; razmerlar XS..3XL; 6 rang; 5 kategoriya; local'da namuna "Klassik futbolka" (oq/qora, 3 bosma joyi).
