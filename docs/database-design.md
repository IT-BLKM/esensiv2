# Rancangan Database Final — presensigps (rewrite: esensiv2)

> Sumber kebenaran untuk pembuatan migration. Disahkan setelah review desain (2026).
> **Diperbarui 26 Nov 2026** setelah modul hari libur, TUKIN, dan konfigurasi lokasi.
> Database: MariaDB/MySQL · Laravel 13 · PHP 8.5.

## Perubahan dari rancangan awal

| # | Perubahan | Alasan |
|---|---|---|
| 1 | `presensi.id_cuti` → **`id_pengajuan`** | Jejak audit (pengajuan mana, disetujui siapa) + pembatalan presisi: `DELETE WHERE id_pengajuan = X` |
| 2 | `presensi` unique dibalik jadi **`(tgl_presensi, id_pegawai)`** | Satu index melayani monitoring per tanggal & cek dobel per pegawai |
| 3 | `foto_*` & `koordinat_*` di presensi → **nullable** | Baris cuti/sakit/dl dibuat otomatis saat approve — tanpa foto & koordinat |
| 4 | `cuti.tabungan_cuti` → **`maks_bawa`** + tabel baru **`saldo_cuti`** | Tabungan = saldo per pegawai per tahun asal (jendela 2 tahun), bukan aturan di tabel master |
| 5 | `pengajuan.disetujui_oleh` → FK **`user.id`** (bukan pegawai) | Approver = admin (role admin) yang belum tentu punya baris pegawai |
| 6 | `user` + kolom **`nama`** | Nama tampilan untuk admin non-pegawai |
| 7 | `CHECK` constraint di `pengajuan` | Menjamin `jenis='cuti'` ⇔ `id_cuti` terisi di level database |
| 8 | Timestamps di tabel master | Konsistensi & audit |
| 9 | `pengajuan` + **`mode_dl`** (`full`/`setengah`, NULL = full) | DL penuh membuat baris `dl` otomatis; DL setengah hanya absen masuk sekali tanpa check-out |
| 10 | Tabel **`hari_libur`** | Hari libur nasional/cuti bersama tidak dihitung sebagai hari kerja |
| 11 | Tabel **`pengaturan`** (key-value) | Admin dapat mengubah lokasi/radius kantor dari aplikasi; `config/presensi.php` tetap menjadi fallback |
| 12 | Tabel **`penggantian_keterlambatan`** | Snapshot audit penggantian TL (target pulang, batas, menit, terpenuhi) |
| 13 | Tabel TUKIN: **`tukin_nominal`, `tukin_periode`, `tukin_koreksi`, `tunjangan_tingkat`** | Rekap TUKIN: nominal per jabatan opsional, kunci periode payroll, koreksi ber-audit, aturan TL/PSW dapat diedit admin |
| 14 | Tabel **`password_reset_log`** | Audit reset password pegawai oleh admin |

---

## Skema

```
user (satu-satunya tabel auth — guard "web")
-----
id               primary key
nama             varchar(255)                      ← tampilan admin
email            varchar(255) unique
password         varchar(255)
role             enum('pegawai','admin')
remember_token   varchar(100) nullable
created_at       datetime
updated_at       datetime

pegawai
-----
id               primary key
user_id          → user.id, unique                  (1 user : 1 pegawai)
id_dept          → departemen.id
nip              varchar(18) unique                 (business key & login NIP)
nama             varchar(255)
jabatan          varchar(255)
foto             varchar(255) nullable
no_hp            varchar(15)
created_at       datetime
updated_at       datetime

departemen
-----
id               primary key
kode             varchar(4) unique
nama             varchar(255)
created_at       datetime
updated_at       datetime

jam_kerja (master shift — TANPA kolom hari)
-----
id               primary key
kode             varchar(4) unique
nama             varchar(25)
awal_masuk       time
jam_masuk        time
akhir_masuk      time
jam_pulang       time
created_at       datetime
updated_at       datetime

cuti (aturan per jenis — tanpa saldo)
-----
id               primary key
kode             varchar(4) unique
nama             varchar(25)
maks_hari        int nullable      -- batas per sekali pengajuan (null = tanpa batas)
kuota            int nullable      -- hak per tahun; alasan penting = null
maks_bawa        int               -- sisa maks yang ditabung per tahun (tahunan=6, lainnya=0)
created_at       datetime
updated_at       datetime

saldo_cuti (TABUNGAN per tahun asal — jendela 2 tahun)
-----
id               primary key
id_pegawai       → pegawai.id
id_cuti          → cuti.id
tahun_sumber     int               -- tahun asal tabungan (sisa kuota tahun X)
saldo            int               -- sisa tabungan tahun itu (max = maks_bawa)
created_at       datetime
updated_at       datetime
UNIQUE(id_pegawai, id_cuti, tahun_sumber)

pengajuan
-----
id               primary key
id_pegawai       → pegawai.id
id_cuti          → cuti.id nullable                -- wajib terisi saat jenis='cuti' (lihat CHECK)
tanggal_mulai    date
tanggal_berakhir date
jenis            enum('cuti','sakit','dl')
mode_dl          enum('full','setengah') nullable   -- NULL lama = full
keterangan       varchar(255)
lampiran         varchar(255) nullable             -- wajib diisi via validasi utk sakit & alasan penting
status           enum('pending','approved','rejected')
disetujui_oleh   → user.id nullable                -- approver = user (admin), bukan pegawai
disetujui_pada   datetime nullable
catatan          varchar(255) nullable             -- alasan reject / catatan admin
created_at       datetime
updated_at       datetime
CHECK ((jenis='cuti' AND id_cuti IS NOT NULL)
    OR (jenis IN ('sakit','dl') AND id_cuti IS NULL))
INDEX(status)

presensi
-----
id               primary key
id_pegawai       → pegawai.id
id_jam_kerja     → jam_kerja.id nullable           -- snapshot jadwal; terisi saat status='hadir'
id_pengajuan     → pengajuan.id nullable           -- ← pengganti id_cuti; terisi ⇔ status ≠ 'hadir'
tgl_presensi     date
jam_masuk        time nullable
jam_keluar       time nullable
foto_masuk       varchar(255) nullable
foto_keluar      varchar(255) nullable
koordinat_masuk  varchar(255) nullable
koordinat_keluar varchar(255) nullable
status           enum('hadir','cuti','sakit','dl')
created_at       datetime
updated_at       datetime
UNIQUE(tgl_presensi, id_pegawai)                    -- anti dobel + query monitoring per tanggal

konfigurasi_jam_kerja
-----
id               primary key
id_pegawai       → pegawai.id
id_jam_kerja     → jam_kerja.id
hari             enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')
UNIQUE(id_pegawai, hari)                            -- satu shift per hari per pegawai
created_at       datetime
updated_at       datetime

hari_libur
-----
id               primary key
tanggal          date UNIQUE
nama             varchar(255)
created_at       datetime
updated_at       datetime

penggantian_keterlambatan (snapshot audit, satu baris per presensi)
-----
id               primary key
id_presensi      → presensi.id, UNIQUE
tanggal          date
jam_mulai        time                 -- jam pulang shift
jam_selesai      time nullable        -- jam keluar aktual
target_pulang    time nullable        -- jam pulang + menit terlambat
batas_pulang     time nullable        -- target + 5 menit
menit_terlambat  smallint
menit_diganti    smallint default 0
bisa_diganti     boolean default true
terpenuhi        boolean default false
catatan          varchar(255) nullable
created_at       datetime
updated_at       datetime

pengaturan (key-value; nilai menang atas config/.env)
-----
id               primary key
kunci            varchar(255) UNIQUE   -- lokasi_latitude, lokasi_longitude, lokasi_radius
nilai            text nullable
created_at       datetime
updated_at       datetime

password_reset_log
-----
id               primary key
admin_id         → user.id
id_pegawai       → pegawai.id
created_at       timestamp

tukin_nominal (nominal dasar per jabatan; OPSIONAL)
-----
id               primary key
jabatan          varchar(255) UNIQUE
nominal          bigint unsigned       -- rupiah
keterangan       varchar(255) nullable
created_at       datetime
updated_at       datetime

tukin_periode (kunci payroll per bulan)
-----
id               primary key
tahun            smallint unsigned
bulan            tinyint unsigned
terkunci         boolean default true
dikunci_oleh     → user.id nullable
dikunci_pada     datetime nullable
dibuka_oleh      → user.id nullable
dibuka_pada      datetime nullable
catatan          varchar(255) nullable
created_at       datetime
updated_at       datetime
UNIQUE(tahun, bulan)

tukin_koreksi (audit trail; tidak pernah dihapus)
-----
id               primary key
id_pegawai       → pegawai.id
tahun            smallint unsigned
bulan            tinyint unsigned
persen           decimal(6,2)          -- boleh negatif
alasan           varchar(255)
dibuat_oleh      → user.id nullable
created_at       datetime
updated_at       datetime
INDEX(id_pegawai, tahun, bulan)

tunjangan_tingkat (aturan TL/PSW yang dapat diedit admin)
-----
id               primary key
kategori         varchar(32)           -- keterlambatan | pulang_cepat
level            varchar(16)           -- TL1–TL3, PSW1–PSW4
min              smallint unsigned     -- menit
max              smallint unsigned nullable  -- NULL = tanpa batas
persen           decimal(5,2)
created_at       datetime
updated_at       datetime
UNIQUE(kategori, level)
INDEX(kategori, min)

konfigurasi_lokasi → tabel `pengaturan` → fallback config/presensi.php ← .env
  PRESENSI_LAT, PRESENSI_LONG, PRESENSI_RADIUS
```

### Catatan index
- `UNIQUE(tgl_presensi, id_pegawai)` melayani: monitoring `WHERE tgl_presensi=?`, rekap `WHERE tgl_presensi BETWEEN ...`, cek dobel/histori `WHERE id_pegawai=? AND tgl_presensi=?` (equality dua kolom → tetap lompat).
- Kolom FK (`id_pegawai`, `id_pengajuan`, dst) otomatis terindeks oleh InnoDB untuk join — `DELETE WHERE id_pengajuan=X` ikut cepat.

---

## Aturan bisnis yang menempel di skema

1. **Hari kerja** = hari dalam rentang yang punya shift di `konfigurasi_jam_kerja` untuk pegawai itu (default bila tidak ada konfigurasi: Senin–Jumat) **dan bukan hari libur** di tabel `hari_libur`. Dipakai untuk: hitung hari pengajuan (cuti Jum–Min = 1 hari), menentukan baris presensi yang dibuat saat approve, dan menandai alpa di laporan.
2. **Tanpa shift di hari itu** → presensi ditolak dengan pesan ramah (tombol disabled di halaman + JSON error terstruktur di server), bukan error 500.
3. **Submit & approve pengajuan** mengecek tabrakan dua kali: ada presensi `hadir` di rentang ATAU pengajuan approved lain tumpang tindih → **tolak utuh** dengan pesan menyebut tanggalnya.
4. **Approve (satu transaksi):** update `status` + `disetujui_oleh/disetujui_pada` + insert baris presensi **hanya untuk hari kerja** (`status = pengajuan.jenis`, `id_pengajuan` terisi). **`saldo_cuti` TIDAK diubah saat approve** — konsumsi dihitung dari baris presensi + saldo.
5. **Batalkan approved:** `DELETE presensi WHERE id_pengajuan = X` + kembalikan status. Saldo tidak perlu disentuh. Pengajuan approved tidak boleh diedit — hanya dibatalkan.
6. **Cuti tahunan — reset tiap 1 Januari (keputusan):**
   - Kuota 12 hari **per tahun kalender**, otomatis "reset" karena dihitung terhadap tahun berjalan — tidak ada kolom saldo tahunan yang harus di-nol-kan.
   - `tersedia(Y) = 12 + saldo(tahun_sumber Y-1) + saldo(tahun_sumber Y-2)` → **maks 24 hari**.
   - Urutan pakai: **tabungan `tahun_sumber` paling lama dulu (FIFO)** — Y-2 dulu, baru Y-1, baru kuota tahun berjalan. *(Keputusan issue #7, 2026-11-19; menggantikan rekomendasi awal "kuota tahun berjalan dulu". PP 11/2017 hanya membatasi total 12/18/24 — Pasal 311/313 — dan tidak mengatur urutan, jadi FIFO adalah kebijakan sistem.)*
   - **Rollover tabungan (otomatis 1 Januari pukul 00.05):** `saldo(tahun_sumber=Y-1) = min(sisa kuota tahun Y-1, maks_bawa)` — diturunkan dari `kuota − jumlah baris presensi cuti tahunan tahun Y-1`.
   - Tabungan `tahun_sumber ≤ Y-2` hangus **otomatis** lewat filter query `tahun_sumber >= Y-2` — tidak perlu proses hapus.
   - Implementasi: command `cuti:rollover-tahunan` (idempotent) + `Schedule::command(...)->yearlyOn(1, 1, '00:05')`. Command yang sama juga dipanggil **lazily** dari `HitungSisaCutiAction` bila rollover tahun sebelumnya belum jalan (jaring pengaman bila cron mati saat 1 Januari).
7. **Bersalin diambil tahun Y** → tabungan tahun Y tidak dibuat dan sisa tahunan tahun Y = 0 (hangus). **Besar:** syarat riwayat 5 tahun (query pengajuan/presensi, tidak perlu kolom). **Alasan Penting:** `kuota = null`, tidak menyentuh `saldo_cuti`.
8. **Semua hitungan cuti diturunkan** dari baris presensi (hari kerja) + `saldo_cuti` — tidak ada angka yang disimpan di dua tempat.
9. **Presensi in/out:** cek radius server-side (Haversine vs `config/presensi.php`), foto base64 → file privat, `UNIQUE(tgl_presensi, id_pegawai)` + transaksi anti race.
10. **Hari pengajuan dihitung hari kerja**, bukan hari kalender (cuti Jum–Min = 1 hari kerja).
11. **Penggantian keterlambatan (TL) hari yang sama:** hanya TL 1–30 menit yang dapat diganti. Target pulang = `jam_pulang` shift + menit terlambat; jendela pulang = target sampai target + 5 menit. TL >30 menit tidak dapat diganti dan langsung mengikuti TL2/TL3. Penggantian hanya menghapus TL; PSW tetap dihitung. Snapshot disimpan di `penggantian_keterlambatan` saat absen pulang; baris lama tanpa snapshot tetap dapat dihitung dari jam masuk/keluar. Di laporan, TL yang sudah diganti tetap tercatat sebagai `H!` tetapi tidak memotong TUKIN.
12. **Hari libur:** tanggal di `hari_libur` bukan hari kerja. Hari libur tidak membuat baris presensi saat approve dan tidak dihitung alpa pada laporan. Admin mengelola lewat menu **Hari Libur**; halaman memberi peringatan bila tahun berjalan belum punya hari libur.
13. **TUKIN — dasar perhitungan:** satu baris presensi `hadir` menghasilkan paling banyak satu tingkat TL dan satu tingkat PSW. Tingkat dan persentase dibaca dari `tunjangan_tingkat`; bila tabel kosong, fallback ke `config/tunjangan.php` (TL1–TL3, PSW1–PSW4). Baris tanpa jam masuk dianggap TL3; baris tanpa jam keluar setelah shift berakhir dianggap PSW4. Shift yang masuk `PRESENSI_KODE_JAM_KERJA_DIABAIKAN` (default `JK03`) dan DL setengah hari tidak dihitung.
14. **TUKIN — nominal & koreksi:** `tukin_nominal` menyimpan nominal dasar per **jabatan** dan bersifat opsional. Bila diisi, potongan rupiah = `nominal × totalPersenAkhir / 100`; bila kosong, laporan hanya menampilkan persentase. `tukin_koreksi` menambah/mengurangi persentase dengan alasan, pembuat, dan waktu; koreksi tidak dihapus, pembatalan dilakukan dengan koreksi bernilai berlawanan. Total akhir = TL + PSW + koreksi.
15. **TUKIN — kunci periode:** `tukin_periode` menyimpan status kunci per bulan. Saat terkunci, presensi baru, approval, dan pembatalan approval yang menyentuh bulan tersebut ditolak; koreksi TUKIN tetap diizinkan karena ber-audit. Admin dapat membuka kunci dari halaman TUKIN.
16. **Keluaran TUKIN:** rekap semua pegawai, rincian per tanggal, rekap per departemen, tren 12 bulan, slip/berita acara per pegawai (PDF), serta ekspor PDF/Excel A4 portrait dengan header Kemenkes. Halaman TUKIN pegawai hanya menampilkan data milik sendiri.
