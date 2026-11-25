# Checklist Keamanan Rilis — esensiv2

Jalankan checklist ini sebelum aplikasi dipakai produksi. Jangan menaruh
nilai rahasia di dokumen ini; isi langsung di environment produksi.

## 1. Environment produksi

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://domain-resmi` (harus HTTPS, tanpa trailing slash)
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_ENCRYPT=true` bila sesi memuat data sensitif
- [ ] `DB_CONNECTION=mysql`, kredensial DB produksi kuat dan tidak memakai user `root`
- [ ] `APP_KEY` sudah digenerate di server produksi; jangan menyalin `.env` lokal
- [ ] `HASH_DRIVER=argon2id_bcrypt` dan `HASH_REHASH_ON_LOGIN=true`
- [ ] `PEGAWAI_DEFAULT_PASSWORD` tidak dibiarkan `123456` setelah go-live

Setelah mengubah `.env`:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 2. HTTPS dan cookie

- [ ] Sertifikat TLS aktif dan redirect HTTP → HTTPS
- [ ] `APP_URL` memakai `https://`
- [ ] Cookie sesi `Secure`, `HttpOnly`, dan `SameSite=Lax`
- [ ] Tidak ada akses langsung ke `storage/` privat; foto presensi hanya lewat
      route ter-auth `pegawai.presensi.foto`
- [ ] `public/storage` hanya untuk file publik (foto profil), bukan foto absen

## 3. Akun admin dan user default

Seeder demo **tidak berjalan di produksi**; `DatabaseSeeder` hanya mengisi
master data. Jangan menjalankan `PenggunaSeeder`/`AktivitasSeeder` di produksi.

Buat admin pertama secara eksplisit (password minimal 12 karakter, diminta
secara interaktif):

```bash
php artisan admin:create admin@domain-resmi
```

Periksa:

```bash
php artisan tinker --execute 'echo App\Models\User::where("role", "admin")->count();'
```

- [ ] Tidak ada akun `admin@esensi.test`, `pegawai@esensi.test`, atau akun demo lain
- [ ] Akun pegawai berasal dari data nyata/ETL, bukan akun contoh
- [ ] Password admin pertama sudah diganti setelah serah terima

## 4. Migrasi dan data

- [ ] Backup database sebelum migrasi:

```bash
mysqldump --single-transaction --routines --triggers \
  -u USER -p DATABASE > backup-sebelum-rilis.sql
```

- [ ] Uji restore backup di database terpisah
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --class=MasterDataSeeder --force` bila master belum ada
- [ ] Jangan pernah menjalankan dump SQL lama dari sistem lama ke repo/server baru
- [ ] Foto absen dan lampiran tetap di `storage/app/private`

## 5. Rotasi password

- [ ] Password admin lama/dump lama tidak dipakai lagi
- [ ] Akun pegawai hasil ETL memakai password awal yang sudah ditetapkan,
      lalu diwajibkan ganti di luar aplikasi
- [ ] Audit akun yang masih memakai password default:

```bash
php artisan pegawai:set-password-default --force
```

- [ ] Setelah semua pegawai mengganti password, pertimbangkan menonaktifkan
      atau mengganti nilai `PEGAWAI_DEFAULT_PASSWORD`

## 6. Pemeriksaan akhir

```bash
php artisan test --compact
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build
```

- [ ] Semua test, Pint, PHPStan, dan build hijau
- [ ] Backup terbaru tersimpan di luar server aplikasi
- [ ] Log produksi tidak memuat password, token, atau hash
- [ ] `APP_DEBUG=false` dan halaman error produksi tidak menampilkan stack trace
