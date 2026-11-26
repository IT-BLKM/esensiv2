# Rancangan Database Final — presensigps (rewrite: esensiv2)

> Sumber kebenaran untuk pembuatan migration. Disahkan setelah review desain (2026).
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

konfigurasi_lokasi → config/presensi.php ← .env
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
