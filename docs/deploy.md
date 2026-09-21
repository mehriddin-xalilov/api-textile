# Serverga chiqarish (production)

Loyiha uchta repodan iborat, uchalasi bitta serverga qo'yiladi:

| Repo | Nima | Server papkasi | Domen |
|------|------|----------------|-------|
| `api-textile` | Laravel API | `/var/www/textile/api` | `api.motex.uz` |
| `admin-textile` | Admin panel (React) | `/var/www/textile/admin` | `admin.motex.uz` |
| `front-textile` | Mijoz sayti + 3D konstruktor | `/var/www/textile/front` | `motex.uz` |

Bitta domen yetarli: qolgan ikkitasi subdomen. DNS da uchalasi ham server IP manziliga A-yozuv.

---

## 1. Server talablari

```
PHP 8.4 (fpm; ext: pdo_pgsql, mbstring, gd yoki imagick, bcmath, zip, intl)
PostgreSQL 15+
Nginx
Node.js 20+ (faqat build uchun)
Composer 2
```

---

## 2. Birinchi o'rnatish

```bash
sudo mkdir -p /var/www/textile && sudo chown -R $USER /var/www/textile
cd /var/www/textile

git clone git@github.com:mehriddin-xalilov/api-textile.git   api
git clone git@github.com:mehriddin-xalilov/admin-textile.git admin
git clone git@github.com:mehriddin-xalilov/front-textile.git front
```

### API

```bash
cd /var/www/textile/api
composer install --no-dev --optimize-autoloader
cp .env.production.example .env        # qiymatlarni to'ldiring (3-bo'lim)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force            # faqat birinchi marta
php artisan passport:keys
php artisan config:cache && php artisan route:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

`db:seed` nima qo'shadi: ruxsatlar, rollar, super-admin, kategoriya va mahsulotlar,
oltita 3D model (`database/seeders/models/*.glb` dan `storage` ga ko'chiriladi),
136 ta ikon, trend so'zlar, bannerlar va statik sahifalar.
Demo (tirikchilik) mahsulotlari **qo'shilmaydi**, ular repoda yo'q.

### Admin va sayt

```bash
cd /var/www/textile/admin
npm ci
cp .env.production.example .env.production   # VITE_API_ROOT ni to'g'rilang
npm run build                                # → dist/

cd /var/www/textile/front
npm ci
cp .env.production.example .env.production
npm run build                                # → dist/
```

---

## 3. `api/.env` (production)

Muhim qiymatlar:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.motex.uz
APP_KEY=                       # php artisan key:generate

DB_CONNECTION=pgsql
DB_DATABASE=textile
DB_USERNAME=textile
DB_PASSWORD=

FRONTEND_URL=https://motex.uz
ADMIN_URL=https://admin.motex.uz

PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=

PAYME_MERCHANT_ID=
PAYME_KEY=
CLICK_MERCHANT_ID=
CLICK_SERVICE_ID=
CLICK_SECRET_KEY=
UZUM_SERVICE_ID=
```

Frontend (build vaqtida o'qiladi):

```
front/.env.production    VITE_API_ROOT=https://api.motex.uz/api/v1
admin/.env.production    VITE_API_ROOT=https://api.motex.uz/api/v1/admin
```

---

## 4. Nginx

```nginx
# Mijoz sayti
server {
    listen 80;
    server_name motex.uz www.motex.uz;
    root /var/www/textile/front/dist;
    index index.html;
    location / { try_files $uri $uri/ /index.html; }
    location ~* \.(glb|woff2?|png|jpg|jpeg|svg)$ { expires 30d; add_header Cache-Control "public, immutable"; }
}

# Admin panel
server {
    listen 80;
    server_name admin.motex.uz;
    root /var/www/textile/admin/dist;
    index index.html;
    location / { try_files $uri $uri/ /index.html; }
}

# API
server {
    listen 80;
    server_name api.motex.uz;
    root /var/www/textile/api/public;
    index index.php;
    client_max_body_size 64M;            # GLB model yuklash uchun

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Yuklangan fayllar: CORS + SVG xavfsizligi (routes/web.php bilan bir xil)
    location /storage/ {
        add_header Access-Control-Allow-Origin *;
        add_header X-Content-Type-Options nosniff;
        add_header Content-Security-Policy "default-src 'none'";
    }
}
```

SSL:

```bash
sudo certbot --nginx -d motex.uz -d www.motex.uz -d admin.motex.uz -d api.motex.uz
```

---

## 5. Yangilash (har safar)

```bash
cd /var/www/textile/api   && git pull && composer install --no-dev -o \
  && php artisan migrate --force && php artisan config:cache && php artisan route:cache
cd /var/www/textile/admin && git pull && npm ci && npm run build
cd /var/www/textile/front && git pull && npm ci && npm run build
sudo systemctl reload php8.4-fpm nginx
```

---

## 6. Hozirgi server (vaqtinchalik)

| Nima | Manzil |
|------|--------|
| Server | `164.92.231.103` (Ubuntu 24.04, PHP 8.4-fpm, PostgreSQL 16) |
| Papkalar | `/var/www/textile/{api,front/dist,admin/dist}` |
| Sinov saytlari (DNS siz) | `http://textile.164.92.231.103.nip.io`, `http://admin-textile...nip.io`, `http://api-textile...nip.io` |
| Nginx | `/etc/nginx/sites-available/textile.conf` |
| Baza | `textile` / foydalanuvchi `textile` |

### motex domenlariga o'tkazish

1. DNS da `motex.uz`, `www.motex.uz`, `admin.motex.uz`, `api.motex.uz` uchun A-yozuvni `164.92.231.103` ga o'zgartiring.
2. Serverda:
   ```bash
   cd /var/www/textile/api
   sed -i 's|^APP_URL=.*|APP_URL=https://api.motex.uz|' .env && php8.4 artisan config:cache
   certbot --nginx -d motex.uz -d www.motex.uz -d admin.motex.uz -d api.motex.uz
   ```
3. Lokalda frontendlarni qayta build qilib yuklang:
   ```bash
   cd web-3d && echo 'VITE_API_ROOT=https://api.motex.uz/api/v1' > .env.production && npx vite build
   rsync -az --delete dist/ root@164.92.231.103:/var/www/textile/front/dist/

   cd ../admin && echo 'VITE_API_ROOT=https://api.motex.uz/api/v1/admin' > .env.production && npm run build
   rsync -az --delete dist/ root@164.92.231.103:/var/www/textile/admin/dist/
   ```

> `APP_URL` muhim: yuklangan fayl va 3D model havolalari shundan yasaladi.
> Noto'g'ri bo'lsa konstruktor modelni yuklay olmaydi.

### Frontendni serverda build qilmaymiz

Bu serverda 1.9 GB RAM va swap yo'q, Node ham o'rnatilmagan.
Shuning uchun `dist` lokalda yig'iladi va `rsync` bilan yuklanadi.

### Passport kalitlari huquqi

`php artisan passport:keys` dan keyin:

```bash
chmod 600 storage/oauth-private.key && chmod 660 storage/oauth-public.key
chown www-data:www-data storage/oauth-*.key
```

Aks holda kirish `Server Error` beradi (kalit fayli huquqi noto'g'ri).

---

## 7. Ishga tushirishdan oldin

1. `APP_DEBUG=false` ekanini tekshiring.
2. Super-admin parolini almashtiring (seed paroli `admin123`).
3. To'lov kalitlarini haqiqiy qiymatga o'tkazing, aks holda to'lov ishlamaydi.
4. `php artisan storage:link` **kerak emas**: fayllar `routes/web.php` orqali beriladi.
5. Kunlik baza zaxirasi: `pg_dump textile | gzip > /backup/textile-$(date +%F).sql.gz`
