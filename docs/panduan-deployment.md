# Panduan Deployment — esensiv2

> Deployment self-hosted: **PHP 8.5 + MariaDB + asset Vite**. Dokumen ini mencakup
> instalasi pertama, prosedur update, dan **cron scheduler yang wajib ada**.
> Sebelum go-live, jalankan juga [`checklist-rilis.md`](checklist-rilis.md).

---

## 1. Kebutuhan server

- **PHP 8.5** (minimal 8.3 sesuai `composer.json`) + ekstensi wajib:
  `pdo_mysql`, `mbstring`, `dom`, `fileinfo`, `openssl`, `curl`
- **Composer 2**
- **MariaDB 11** (atau MySQL 8)
- **Node.js 20+** dan npm — hanya dipakai untuk build asset
- Web server (Nginx/Apache) dengan **document root = folder `public/`**
- `cron` / `crontab` untuk menjalankan scheduler Laravel

Cek kesiapan mesin:

```bash
php -v
composer check-platform-reqs
node -v && npm -v
```

Pastikan `storage/`, `storage/framework/`, `storage/logs/`, dan `bootstrap/cache/`
dapat ditulis oleh user web server.

---

## 2. Siapkan environment

```bash
git clone https://github.com/IT-BLKM/esensiv2.git
cd esensiv2
cp .env.example .env
php artisan key:generate
```

Isi `.env` produksi dengan nilai sebenarnya. Poin yang wajib diperiksa:

| Variabel | Catatan |
|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | jangan pernah `true` di produksi |
| `APP_URL=https://domain-resmi` | HTTPS, tanpa trailing slash |
| `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | pakai user DB khusus, bukan `root` |
| `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` | tabelnya dibuat oleh `migrate` |
| `SESSION_SECURE_COOKIE=true` | cookie hanya lewat HTTPS |
| `HASH_DRIVER=argon2id_bcrypt`, `HASH_REHASH_ON_LOGIN=true` | lihat checklist rilis |
| `PEGAWAI_DEFAULT_PASSWORD` | ganti dari nilai default setelah go-live |
| `PRESENSI_LAT`, `PRESENSI_LONG`, `PRESENSI_RADIUS` | koordinat & radius kantor (meter) |
| `PRESENSI_ZONA_WAKTU=Asia/Jakarta` | dipakai scheduler cuti dan penilaian tanggal absen |
| `PRESENSI_NAMA_KANTOR`, `PRESENSI_ALAMAT_KANTOR` | header laporan PDF/Excel |

> `.env` **tidak boleh di-commit** dan jangan menyalin `.env` lokal ke produksi.
> Nilai lokasi kantor juga bisa diubah admin lewat menu **Konfigurasi**; nilai
> di tabel `pengaturan` menang atas `.env`.

---

## 3. Deploy pertama kali

Jalankan dari root aplikasi, sebagai user web server:

```bash
# 1. Dependency PHP (tanpa dev); composer.lock sudah terkunci
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 2. Dependency & build asset frontend
npm ci
npm run build

# 3. Database
php artisan migrate --force
php artisan storage:link

# 4. Cache produksi (lakukan setelah .env final)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Admin pertama (password diminta interaktif)
php artisan admin:create admin@domain-resmi
```

Catatan:

- `npm run build` menghasilkan `public/build/manifest.json`; halaman akan error
  *Unable to locate file in Vite manifest* bila build tidak dijalankan.
- `php artisan storage:link` membuat symlink `public/storage` untuk file publik.
  **Foto absen dan lampiran tetap privat** di `storage/app/private`, diakses
  hanya lewat route ter-auth.
- **Jangan** menjalankan `php artisan migrate --seed`, `PenggunaSeeder`, atau
  `AktivitasSeeder` di produksi. Seeder demo hanya untuk lokal. Jika master
  data belum ada, jalankan seeder master sesuai `checklist-rilis.md`
  (`MasterDataSeeder`, `HariLiburSeeder`).
- Backup database sebelum `migrate --force` (lihat `checklist-rilis.md`).

---

## 4. Update aplikasi

```bash
# Opsional: maintenance mode agar tidak ada request di tengah deploy
php artisan down

git pull origin main

# Ulangi langkah deploy bila composer.lock / package-lock.json berubah
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link

# Buang cache lama, lalu bangun ulang
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
```

`optimize:clear` penting setelah perubahan `.env`, route, atau view agar
aplikasi tidak memakai versi lama. Bila tidak memakai maintenance mode,
langkah `down`/`up` boleh dilewat.

---

## 5. Cron scheduler (WAJIB)

Aplikasi memakai satu scheduled task: **rollover tabungan cuti tahunan** setiap
**1 Januari pukul 00:05 waktu kantor** (`Asia/Jakarta`). Command ini idempotent
dan juga dipanggil lazily oleh `HitungSisaCutiAction` bila cron tidak jalan,
tetapi **cron tetap wajib dipasang** agar rollover berjalan tepat waktu.

Tambahkan ke `crontab -e` (sesuaikan path absolut dan user web server):

```cron
* * * * * cd /path/ke/esensiv2 && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi:

```bash
php artisan schedule:list
```

Output yang diharapkan (jadwal disimpan dalam UTC; `17:05 31 Desember UTC`
= `00:05 1 Januari Asia/Jakarta`):

```text
5 17 31 12 * php artisan cuti:rollover-tahunan .. Next Due: ...
```

Uji manual tanpa menunggu tanggal 1 Januari:

```bash
php artisan cuti:rollover-tahunan --tahun=2026
```

> `schedule:list` membaca cache locking. Pastikan MariaDB hidup dan
> `CACHE_STORE=database` sudah termigrasi; jika tidak, perintah gagal dengan
> `SQLSTATE ... Connection refused`.

---

## 6. Verifikasi pasca-deploy

```bash
php artisan migrate:status
php artisan schedule:list
curl -I https://domain-resmi/login
ls -l public/build/manifest.json public/storage
```

- [ ] `migrate:status` → semua migrasi **Ran**
- [ ] `schedule:list` → menampilkan `cuti:rollover-tahunan`
- [ ] `GET /login` → HTTP 200, aset Vite termuat (tidak ada error manifest)
- [ ] `public/storage` → symlink aktif
- [ ] Log produksi tidak berisi password/token
- [ ] Checklist di `checklist-rilis.md` sudah dicentang

---

## 7. Rangkuman perintah (copy-paste)

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan schedule:list
```

> Untuk update, awali dengan `git pull origin main` dan akhiri dengan
> `php artisan up` bila memakai maintenance mode.
