# Panduan Kolaborasi GitHub — esensiv2

> Untuk seluruh tim. Baca sekali dari atas ke bawah, lalu simpan sebagai pegangan.
> Sebagian besar masalah tim pemula muncul karena **push langsung ke `main`** dan
> **branch yang sudah basi** — dua hal itu yang dijaga paling ketat di panduan ini.

---

## 0. Konsep singkat (baca ini dulu)

```
main  ──── branch "utama", SELALU dalam keadaan bisa dijalankan. TIDAK BOLEH ditulis langsung.
  │
  ├── feat/tambah-cuti ──┐
  ├── fix/presensi-null ─┼── branch kerja kamu (satu tugas = satu branch)
  └── docs/panduan ──────┘
                            ↓ selesai → Pull Request → review → merge ke main
```

- **Repository** = proyek. **Branch** = salinan kerja kamu. **Pull Request (PR)** = permintaan "tolong tarik kerjaan saya ke `main`".
- Setiap PR otomatis diuji **CI (Pint + Pest)**. Kalau merah, PR belum boleh di-merge.
- Aturan emas: **`main` hanya berubah lewat PR yang sudah lulus CI.**

---

## 1. Setup sekali saja (per orang)

```bash
# 1. Clone repo (sekali di awal)
git clone https://github.com/ORG-NAMA-REPO.git
cd NAMA-REPO

# 2. Pasang dependency (Pint, Pest, dll — sudah terkunci di composer.lock)
composer install

# 3. Siapkan file environment (JANGAN pernah di-commit)
cp .env.example .env
php artisan key:generate

# 4. Verifikasi semuanya sehat
vendor/bin/pest        # minimal harus jalan (boleh 0 test di awal)
vendor/bin/pint --test # harus hijau
```

Kalau pakai GitHub CLI (`gh`), sekali login saja:

```bash
gh auth login
```

> **Syarat mesin:** PHP 8.5, ekstensi `pdo_mysql mbstring dom fileinfo`, MariaDB, Node.js untuk build asset.
> Cek dengan `php -v` dan `composer check-platform-reqs`.

---

## 2. Ritual harian — ambil terbaru sebelum mulai kerja

**Selalu lakukan ini sebelum mulai kerja**, supaya tidak menumpuk perubahan lama:

```bash
git switch main          # pindah ke branch utama (git lama: git checkout main)
git pull                 # ambil & gabungkan terbaru dari GitHub
composer install         # kalau composer.lock berubah, dependency ikut berubah
```

Mau sekalian cabut ke branch kerja? `gh` bisa sekaligus:

```bash
gh pr checkout 12        # checkout PR nomor 12 beserta branch-nya + info terbaru
```

---

## 3. Mulai kerja — satu tugas = satu branch

Jangan pernah langsung menulis di `main`. Buat branch dari `main` yang sudah terbaru:

```bash
git switch main && git pull
git switch -c feat/15-seed-cuti
#        ↑ perintah baru   ↑ nama branch baru
```

### Konvensi nama branch (wajib)

```
<jenis>/<nomor-issue>-<deskripsi-pendek>
```

| Jenis | Untuk apa | Contoh |
|---|---|---|
| `feat` | fitur baru | `feat/15-seed-cuti` |
| `fix` | memperbaiki bug | `fix/30-presensi-null` |
| `refactor` | rapikan kode tanpa ubah perilaku | `refactor/33-action-presensi` |
| `docs` | dokumentasi | `docs/6-keputusan-desain` |
| `test` | nambah/memperbaiki test | `test/44-feature-presensi` |
| `chore` | hal teknis (CI, dependency) | `chore/ci-pint-pest` |

Nomor issue dipakai **supaya semua tahu branch ini mengerjakan apa** — lihat dulu nomornya di tab *Issues*.

---

## 4. Commit — kecil, jelas, dan rapi

```bash
git status                 # lihat apa saja yang berubah (lakukan SEBELUM commit)
git add app/Actions/StorePresensiAction.php
git commit -m "feat: validasi radius presensi di server"
git push -u origin feat/15-seed-cuti
```

### Aturan commit

1. **Pesan format Conventional Commits** — `jenis: penjelasan singkat`
   `feat:` · `fix:` · `refactor:` · `test:` · `docs:` · `chore:`
2. **Satu commit = satu ide.** Jangan mencampur "nambah fitur + ganti semua format".
3. **`git add` yang spesifik**, jangan asal `git add .` (rawan ikut file `.env`/foto/SQL).
4. **Commit kecil-kecil lebih aman** daripada satu commit raksasa yang susah di-review.
5. Tahap dulu sebelum commit: `git add -p` → tampil per bagian, tekan `y`/`n`.

### File yang TIDAK BOLEH pernah masuk git

| File | Alasan |
|---|---|
| `.env` | kunci rahasia (password DB, APP_KEY) |
| `u671765567_presensi.sql` / `*.sql` dump | **data pribadi asli** — NIP, no HP, hash password |
| `storage/app/public/*` foto absen | foto karyawan |
| `vendor/`, `node_modules/` | sudah di `.gitignore` |

Kalau tidak sengaja ter-commit: **jangan `git push`**, lalu
`git rm --cached <file>` + `git commit --amend` (atau minta bantuan — kalau sudah ter-push dan berisi data pribadi, harus dianggap bocor dan segera ditangani).

---

## 5. Membuat Pull Request

### Cara A — via GitHub CLI (disarankan, singkat)

```bash
git push -u origin feat/15-seed-cuti
gh pr create --fill --base main
#   atau tulis sendiri:
gh pr create \
  --title "feat: seed 4 jenis cuti PNS (#15)" \
  --body "Menutup issue #15. Menambahkan CutiSeeder + enum jenis. Test: vendor/bin/pest hijau." \
  --base main
```

### Cara B — via browser

1. `git push` dulu, lalu buka halaman repo → muncul banner kuning **"Compare & pull request"** → klik.
2. Isi:
   - **Title:** `feat: seed 4 jenis cuti PNS (#15)` — `#15` otomatis jadi tautan ke issue.
   - **Body:**
     ```
     Menutup #15
     - Apa yang berubah
     - Cara menguji (langkah manual, kalau ada)
     - Catatan / keputusan yang perlu diketahui reviewer
     ```
   - **Base:** `main` ← pastikan ini.
3. Klik **Create pull request**.

### Checklist sebelum buka PR

- [ ] `vendor/bin/pint` sudah dijalankan (kode rapi)
- [ ] `vendor/bin/pest` hijau di mesin sendiri
- [ ] Tidak ada file terlarang ikut (cek `git status`)
- [ ] Branch sudah di-`rebase`/pull terbaru dari `main` (langkah 2)
- [ ] Ada kaitan ke issue (`Menutup #15`), supaya issue tertutup otomatis saat merge

> **Menutup issue otomatis:** tulis `Menutup #15` / `Closes #15` / `Fixes #15` di body PR.

---

## 6. Review dan merge

1. Buka PR → tab **Files changed** → reviewer beri komentar (`+` pada baris kode).
2. Penulis menjawab komentar / mengubah kode.
3. **Cek tab Checks** — harus hijau semua (**Pint** dan **Pest**).
4. Merge. **Pilih salah satu, jangan campur:**

| Tombol | Kapan dipakai |
|---|---|
| **Squash and merge** ✅ | PR berisi banyak commit kecil → jadikan **satu** commit rapi di `main` |
| **Merge commit** | PR berisi rangkaian commit yang harus tetap terpisah (mis. riwayat migrasi) |
| **Rebase and merge** | ingin riwayat lurus tanpa commit penggabung |

Rekomendasi tim: **Squash and merge** untuk PR kecil-sedang.

5. Setelah merge, **bersihkan branch**:
   ```bash
   gh pr merge 42 --squash --delete-branch   # merge + hapus branch remote & lokal
   ```
   Atau kalau lewat browser: centang **"Delete branch"** saat merge.

6. Kembali ke kerjaan berikutnya:
   ```bash
   git switch main && git pull && composer install
   ```

---

## 7. Mengambil pekerjaan dari papan

1. Buka tab **Issues** → ambil issue yang belum ada yang menulis namanya.
2. Komentar **"Saya kerjakan ini"** lalu minta di-assign (atau assign sendiri kalau punya hak) → mencegah dua orang kerja hal yang sama.
3. Pindahkan kartu ke **In progress** di papan Projects.
4. Kerjakan (langkah 3–5) → buka PR → **merge = kartu pindah ke Done, issue tertutup otomatis**.

**Aturan ukuran:** satu PR idealnya selesai dalam **satu sesi kerja**. Kalau terasa besar, pecah dulu jadi issue/PR baru — PR 800 baris tidak akan pernah di-review dengan serius.

### Sudah buntu / butuh diskusi?

Jangan diam. Komentar di issue-nya, misal:
> "Di poin 3 saya tidak yakin apakah hari Minggu dihitung. Menurut @X bagaimana?"
> Kalau butuh keputusan, tulis `[DECISION]` di judul issue-nya.

---

## 8. Kalau CI merah

Buka tab **Checks** pada PR → klik job yang gagal → baca log bagian akhir.

| Pesan error | Yang dilakukan |
|---|---|
| Pint: `code style error` | lokal: `vendor/bin/pint` lalu `git add -A && git commit --amend` / commit baru, `git push` |
| `SQLSTATE[HY000] Connection refused` | langkah setup DB test belum lengkap (lihat bagian setup) |
| `MissingAppKeyException` | `.env` belum ada / belum `key:generate` |
| Test gagal tertentu | jalankan lokal `vendor/bin/pest --filter=NamaTest` untuk detail |
| `Your lock file does not contain a compatible set of packages` | `composer.lock` belum ikut di-commit — jalankan `composer update` lalu commit kedua file |

Selalu **perbaiki lalu push ke branch yang sama** — PR otomatis ter-update. Jangan tutup PR lalu buka baru.

---

## 9. Konflik merge (`git merge` / `git pull` menolak)

Artinya dua orang mengubah baris yang sama.

```bash
git switch main && git pull
git switch feat/15-seed-cuti
git merge main            # atau: git rebase main
```

Kalau muncul `CONFLICT (content)`:

1. Buka file yang ditandai → cari `<<<<<<< HEAD`
2. Pilih isi yang benar, **hapus semua tanda** `<<<<<<<`, `=======`, `>>>>>>>`
3. Simpan, lalu:
   ```bash
   git add <file-yang-beres>
   git merge --continue     # untuk rebase: git rebase --continue
   git push --force-with-lease   # HANYA untuk rebase (lihat catatan)
   ```
4. `vendor/bin/pest` dulu sebelum push.

**Aturan anti-konflik:**
- Selalu `git pull` di `main` **sebelum** mulai kerja.
- Jangan menyentuh file yang sama berlama-lama dalam satu branch — selesaikan cepat, merge cepat.
- Jangan pernah ubah file **tanpa sebab** (mis. format ulang satu file yang tidak tersentuh tugasmu) — membuat konflik palsu untuk orang lain.

> ⚠️ `--force` **hanya** untuk branch milik sendiri setelah rebase. Pernah dipakai di branch orang lain = kerjaan mereka hilang.

---

## 10. Cheat sheet (cetak / simpan)

```bash
# ---- Harian ----
git switch main && git pull && composer install      # ambil terbaru

# ---- Mulai tugas ----
git switch main && git pull
git switch -c feat/15-seed-cuti                      # branch baru

# ---- Kerja ----
git status                                           # cek sebelum commit
git add app/Actions/X.php
git commit -m "feat: penjelasan singkat"
git push -u origin feat/15-seed-cuti

# ---- Selesai ----
vendor/bin/pint && vendor/bin/pest                   # wajib hijau sebelum PR
gh pr create --fill --base main                      # buka PR
gh pr checks                                         # pantau CI
gh pr merge 42 --squash --delete-branch              # merge + bersihkan

# ---- Darurat ----
git stash                                            # simpan perubahan sementara
git stash pop                                        # ambil lagi
git checkout -- file.php                             # buang perubahan di 1 file (TIDAK BISA DIKEMBALIKAN)
git reset --hard origin/main                         # paksa branch lokal = remote (kerja lokal hilang!)
```

---

## 11. Jangan lakukan ini

| ❌ Jangan | ✅ Seharusnya |
|---|---|
| `git push` langsung ke `main` | buka PR, lalu merge |
| Commit `.env`, dump SQL, foto absen | biarkan di `.gitignore` |
| `git add -A` tanpa cek `git status` | `git add <file spesifik>` |
| PR 30 file / 1.000 baris | pecah jadi PR kecil |
| Push `--force` ke branch orang | hanya `--force-with-lease` di branch sendiri |
| Merge PR dengan CI merah | perbaiki dulu |
| Diam saat buntu | komentar di issue / minta review |
| Mengubah desain database seenaknya | catat di `docs/database-design.md` lewat PR `docs/` |

---

## 12. Alur lengkap dalam satu gambar

```
git pull di main
     │
     ▼
buat branch  ──►  kerja  ──►  pint + pest hijau  ──►  commit + push
                                                              │
                                                              ▼
                                                    buka PR (tautkan issue)
                                                              │
                                            ┌─────────────────┴─────────────────┐
                                            ▼                                   ▼
                                     CI Checks hijau                        CI merah
                                            │                                   │
                                            ▼                                   ▼
                                     reviewer approve                     perbaiki + push
                                            │                            (ke branch yang sama)
                                            ▼                                   │
                              Squash and merge ◄────────────────────────────────┘
                                            │
                                            ▼
                              branch dihapus · issue tertutup · git pull lagi
```

## 13. Konvensi route & struktur URL

> Bagian ini **keputusan desain kode** (23 Nov 2026), ditulis di sini supaya jadi pegangan
> sebelum route pertama `esensiv2` ditulis. Alasan panjangnya: route di `presensigps` berantakan —
> mutasi lewat `GET`, baca data lewat `POST`, `{nip}` di URL tanpa policy, dua guard, nama tidak konsisten.
> Jangan menyalin bentuk route lama.

### Aturan yang wajib dipatuhi

| # | Aturan | Masalah lama yang ditutup |
|---|---|---|
| 1 | **`GET` hanya membaca.** Semua perubahan data `POST`/`PATCH`/`DELETE` | `GET /pegawai/{nip}/resetpassword` & `.../delete` bisa dipicu CSRF lewat link |
| 2 | **`Route::resource` + route model binding** — tidak ada `{nip}` manual di URL | `POST /pegawai/{nip}/update` tanpa cek pemilik = IDOR |
| 3 | **Selalu `route()` di blade**, tanpa URL literal | sekali ganti prefix, semua tautan rusak |
| 4 | **Satu guard `web`, satu halaman login, redirect by role** | dua guard, dua login, dua logout (`/proseslogin`, `/prosesloginadmin`) |
| 5 | **Otorisasi di Policy**, bukan cek `role` di dalam controller | logika akses tercecer & mudah terlewat |
| 6 | **Validasi input di FormRequest** | validasi tercampur di controller |
| 7 | **`throttle:5,1` di login + pesan error generik** | brute force tanpa jejak |
| 8 | **Hanya sintaks array** `[X::class, 'method']` | string `'App\Http\Controllers\X@method'` (gaya Laravel ≤8) |
| 9 | **Satu resource `Cuti`**, jenis = data (C01–C04), bukan 4 controller | `Dinasluar` + `Cutisakit` + `Izincuti` + `Cuti` saling duplikat |

### Bentuk target

```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('pegawai', PegawaiController::class)->except('show');
        Route::resource('departemen', DepartemenController::class)->except('show');
    });
});
```

- **Satu prefix per peran:** `/admin/*` untuk `role:admin`, sisanya untuk pegawai.
- **Nama route berangkai:** `admin.pegawai.index`, `admin.pegawai.update`, dst.
- **Jangan menulis 60 route sekaligus.** Ikuti urutan issue kecil: **#18 (login) → #19 (middleware role) → #20 (throttle) → #21 (password)** — tiap langkah mengunci bentuk route berikutnya.

### Keputusan terkait: reset password

**Reset password dilakukan oleh ADMIN, bukan via email.** Alasan: email user hasil ETL masih
`{nip}@presensi.test` (domain `.test` direserve RFC 2606 → **tidak bisa menerima email**), dan
admin/HR memang sudah tahu seluruh pegawai. Alur lupa-password via email **baru dipertimbangkan
belakangan**, setelah ada domain email kerja nyata + SMTP. Rincian di issue **#21**.

---

Ada yang belum tercakup? Tambahkan sebagai issue label `docs` — panduan ini juga harus ikut berkembang.
