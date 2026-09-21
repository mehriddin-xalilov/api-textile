# CI/CD (GitHub Actions)

Har bir repo `main` ga push qilinganda avtomatik serverga chiqadi.

| Repo | Workflow | Nima qiladi |
|------|----------|-------------|
| `api-textile` | `.github/workflows/tests.yml` | Pint, PHPStan, 32 test (PostgreSQL bilan) |
| `api-textile` | `.github/workflows/deploy.yml` | Serverda `git pull`, composer, migrate, cache, fpm reload |
| `front-textile` | `.github/workflows/deploy.yml` | `npm ci` → `tsc` → `vite build` → `rsync dist` |
| `admin-textile` | `.github/workflows/deploy.yml` | `npm ci` → build → `rsync dist` |

Frontendlar GitHub runnerida yig'iladi: serverda RAM kam (1.9 GB, swap yo'q).

---

## Har bir repoga qo'shiladigan Secrets

GitHub → repo → **Settings → Secrets and variables → Actions → New repository secret**

### Uchalasiga umumiy

| Nom | Qiymat |
|-----|--------|
| `SSH_HOST` | `164.92.231.103` |
| `SSH_USER` | `root` |
| `SSH_PRIVATE_KEY` | serverdagi `/root/.ssh/gh_deploy` faylining to'liq matni |

`SSH_PRIVATE_KEY` ni olish:

```bash
ssh root@164.92.231.103 "cat /root/.ssh/gh_deploy"
```

`-----BEGIN` dan `-----END OPENSSH PRIVATE KEY-----` gacha hammasini nusxalang.

### `api-textile`

| Nom | Qiymat |
|-----|--------|
| `API_HEALTH_URL` | `http://api-textile.164.92.231.103.nip.io/api/v1/site` |

### `front-textile`

| Nom | Qiymat |
|-----|--------|
| `VITE_API_ROOT` | `http://api-textile.164.92.231.103.nip.io/api/v1` |
| `DEPLOY_PATH` | `/var/www/textile/front/dist` |
| `SITE_URL` | `http://textile.164.92.231.103.nip.io` |

### `admin-textile`

| Nom | Qiymat |
|-----|--------|
| `VITE_API_ROOT` | `http://api-textile.164.92.231.103.nip.io/api/v1/admin` |
| `DEPLOY_PATH` | `/var/www/textile/admin/dist` |
| `SITE_URL` | `http://admin-textile.164.92.231.103.nip.io` |

> motex domenlariga o'tgandan keyin `VITE_API_ROOT`, `SITE_URL` va `API_HEALTH_URL`
> qiymatlarini `https://...motex.uz` ga almashtiring. Kodga tegish shart emas.

---

## Qo'lda ishga tushirish

GitHub → repo → **Actions** → kerakli workflow → **Run workflow**.

## Deploy kaliti

Serverda `github-actions@textile` nomli alohida SSH kalit yaratilgan
(`/root/.ssh/gh_deploy`), uning ochiq qismi `authorized_keys` ga qo'shilgan.
Kalitni bekor qilish: shu qatorni `authorized_keys` dan o'chirish kifoya.
