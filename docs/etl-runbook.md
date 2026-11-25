# Runbook ETL — Impor Data Lama `presensigps` → `esensiv2`

> Sumber data: dump **di luar repo** (mis. `~/data/u671765567_presensi.sql`, 8 MB — **jangan pernah masuk git**).
> Keputusan: issue #7 (2026-11-19). Rancangan target: `docs/database-design.md`.
> Prinsip: **jalankan sekali di lokal/staging, bukan di CI**; semua langkah idempoten; dry-run sebelum eksekusi nyata.

---

## 0. Keputusan yang sudah disahkan (issue #7)

| # | Keputusan |
|---|---|
| 1 | **ETL terpilih** — master + riwayat pengajuan (penuh) + presensi **12 bulan terakhir**. **Semua akun pegawai memakai password awal `123456`** (config `PEGAWAI_DEFAULT_PASSWORD`, keputusan QA 25 Nov 2026); hash lama maupun hash admin tidak diimpor |
| 2 | `cuti`: `maks_hari`/`kuota` = **12 / 90 / 30 / 90** (C01/C02/C03/C04); `maks_bawa` = **6** untuk tahunan, **0** lainnya |
| 3 | Urutan pakai tabungan = **paling lama dulu (FIFO)**; dasar hitung sisa = kuota tahun berjalan; rollover tetap `min(sisa, 6)` |

---

## 1. Prasyarat

- [ ] Migration `#8–#17` selesai & `php artisan migrate` jalan di database target.
- [ ] Seeder admin baru sudah jalan (issue #16) — **user admin lama TIDAK diimpor**.
- [ ] Dump SQL sudah disalin **ke luar repo**; lokasinya dicatat di `.env` lokal (mis. `ETL_DUMP_PATH`) atau argumen command.
- [ ] Backup database target dicatat (command ETL punya mode `--dry-run` dulu).

---

## 2. Pemetaan tabel lama → baru

| Tabel lama | Tabel baru | Jumlah sumber | **Target** |
|---|---|---|---|
| `departemen` | `departemen` | 19 | **19** |
| `jam_kerja` | `jam_kerja` | 3 | **3** |
| `master_cuti` | `cuti` (+ aturan baru) | 4 | **4** |
| `pegawai` + `users` | `user` + `pegawai` | 98 (+1 admin lama) | **97 user pegawai + 1 admin seeder** (1 pegawai NIP 16 digit gugur, lihat §3.1) |
| `konfigurasi_jamkerja` | `konfigurasi_jam_kerja` | 1.256 | **677** (lihat §3) |
| `pengajuan_cuti` | `pengajuan` | 1.987 | **1.987** (tanpa buang) |
| `presensi` (12 bln) | `presensi` | 10.662 | **10.602** (lihat §3) |
| `konfigurasi_lokasi` | `.env` → `config/presensi.php` | 1 | 3 variabel |
| — | `saldo_cuti` (init) | — | **173 baris** (lihat §5) |
| — | `saldo_cuti` (init) | — | **±175 baris** (Y=2026; lihat §5) |

### 2.1 Detail kolom

**departemen:** `kode_dept→kode`, `nama_dept→nama`.
**jam_kerja:** `kode_jam_kerja→kode`, `nama_jam_kerja→nama`, `awal_jam_masuk→awal_masuk`, `jam_masuk→jam_masuk`, `akhir_jam_masuk→akhir_masuk`, `jam_pulang→jam_pulang`.
**cuti** (angka dari keputusan #2, bukan `jml_hari` lama):

| kode | nama | maks_hari | kuota | maks_bawa | keterangan |
|---|---|---|---|---|---|
| C01 | Cuti Tahunan | 12 | 12 | 6 | legacy lama `jml_hari=24` = batas 18/24 Pasal 313, bukan hak tahunan |
| C02 | Cuti Bersalin | 90 | 90 | 0 | |
| C03 | Cuti Alasan Penting | 30 | 30 | 0 | keputusan #7 menurunkan dari `null` |
| C04 | Cuti Besar | 90 | 90 | 0 | syarat 5 tahun via query, bukan kolom |

**user** (1 per pegawai, dibuat baru):
- `email` = `{nip}@presensi.test` (TLD `.test` cadangan, tidak bisa dikirim — login utama via NIP, lihat issue #18)
- `password` = `Hash::make(PEGAWAI_DEFAULT_PASSWORD)` — default **`123456`**. Hash dari aplikasi lama **tidak** diimpor
- Setelah ETL, jalankan `php artisan pegawai:set-password-default --force` bila database sudah punya akun pegawai dari run sebelumnya (command hanya menyentuh role `pegawai`, bukan admin)
- `nama` = `nama_lengkap` lama, `role` = `pegawai`
- `users` lama (1 admin + hash yang pernah terekspos) → **dilewati total**
- ⚠️ Password awal `123456` sangat lemah. Beri tahu seluruh pegawai di luar aplikasi agar segera mengganti password; audit akun yang masih default.

### Algoritma hash password (Argon2id, issue #96)

- Password **baru** dibuat dengan **Argon2id** (`$argon2id$`).
- Driver `argon2id_bcrypt` tetap bisa memverifikasi hash **bcrypt lama**.
- Setelah login berhasil dengan hash bcrypt, hash otomatis di-rehash menjadi
  Argon2id (`HASH_REHASH_ON_LOGIN=true`).
- Parameter env:
  - `HASH_DRIVER=argon2id_bcrypt`
  - `ARGON_MEMORY=65536` (KiB; 64 MiB per verifikasi)
  - `ARGON_TIME=4`
  - `ARGON_THREADS=1`
- Jangan mengganti driver menjadi `argon2id` biasa selama masih ada hash
  bcrypt; akun lama akan gagal login.
- Rollback ke kode bcrypt-only hanya aman bila **belum ada** hash Argon2id.
  Setelah rehash berjalan, akun terdampak perlu reset atau tetap memakai
  driver dual ini sampai semua hash dikonversi.

**pegawai:** `nip→nip` (unique), `nama_lengkap→nama`, `jabatan`, `no_hp`, `kode_dept→id_dept` (via lookup `kode`), `foto` → lihat keputusan kecil §6, `user_id` → user hasil pembuatan di atas.

**konfigurasi_jam_kerja:** `nip→id_pegawai`, `hari` sudah `Senin…Minggu` (langsung cocok enum), `kode_jam_kerja→id_jam_kerja` (uppercase dulu).

**pengajuan:** `nip→id_pegawai`, `kode_cutii→id_cuti` (hanya jenis `c`; `d`/`s` → `NULL`), `status` `c/s/d→jenis` `cuti/sakit/dl`, `tgl_cuti_dari/sampai→tanggal_mulai/tanggal_berakhir`, `keterangan`, `doc_sid→lampiran` (lihat §6), `status_approved` `1→approved` / `0→pending` / `2→rejected` (**22 baris `0` = pending, semua Juni 2026**), `disetujui_oleh/disetujui_pada/catatan` = `NULL` (data lama tidak menyimpan approver).
→ Kolom `kode_cuti` (char 35) lama **tidak disimpan** di tabel baru; dipakai sebagai **peta di memori** saat import presensi (satu run = satu command, jadi tidak perlu tabel map).

**presensi:** `nip→id_pegawai`, `tgl_presensi`, `jam_in/out→jam_masuk/jam_keluar`, `lokasi_in/out→koordinat_masuk/keluar`, `kode_jam_kerja→id_jam_kerja` (uppercase; `NULL` untuk baris non-hadir), `kode_cuti→id_pengajuan` (via peta di memhir), `status` → lihat §3 butir 4, `foto_*` → lihat §6.

---

## 3. Aturan pembersihan (data kotor NYATA di dump)

Ditemukan dari analisis langsung — semua aturan ini **wajib** ada di command:

1. **Baris header nyangkut (2 baris):** `id=12606` & `id=20659` berisi teks header (`nip`, `kode`, `foto_in`, …) dengan tanggal `0000-00-00`.
   → **Drop** semua baris dengan tanggal tidak valid / `0000-00-00` / `nip` bukan 18 digit.
2. **NIP tidak valid / asing:**
   - **NIP bukan 18 digit:** pegawai `1803144103030001` (Lia Tri Aida Saputri, 16 digit) **beserta seluruh barisnya** — 1 pegawai, 5 jadwal, 34 presensi (di jendela) → pegawai valid tinggal **97**.
   - **NIP asing:** `197502131006041011`, `197425091998031003` (presensi, sudah tidak ada di master) + `1601226106010005` (7 jadwal, 16 digit) + `196506101989011001` (14 jadwal, 18 digit tapi bukan pegawai).
   → **Drop** baris yang `nip` tidak 18 digit atau tidak ada di tabel `pegawai`; catat ke log.
3. **Duplikat:**
   - `presensi`: **59 pasang `(nip, tgl)`** (26 di antaranya di jendela 12 bulan) — melanggar `UNIQUE(tgl_presensi, id_pegawai)`.
     → **Drop** dengan pemenang = baris paling lengkap (`jam_in` terisi → `jam_out` terisi → `foto` terisi → `id` terkecil).
   - `konfigurasi_jam_kerja`: **553 duplikat `(nip, hari)`** (drop kemunculan pertama, setelah NIP bermasalah dibuang di butir 2).
     → **Drop** simpan kemunculan pertama. Hasil bersih: **677 baris** (dari 1.256: −553 duplikat −12 NIP tidak 18 digit −14 NIP bukan pegawai; 4 hari kosong milik 2 pegawai — biarkan, aturan default hanya untuk pegawai tanpa konfigurasi sama sekali).
4. **Status presensi ≠ jenis pengajuan (5 baris):** presensi `'c'` yang menunjuk pengajuan `d` (DL).
   → **Selaraskan ke jenis pengajuan** (`d`) — pengajuan adalah sumber kebenaran; konsisten dengan `id_pengajuan` + CHECK di skema baru. *(Di jendela 12 bulan jumlahnya 0; aturan tetap ditulis untuk keamanan.)*
5. **Baris non-hadir tanpa kode pengajuan (2 baris: 1 `'c'`, 1 `'s'`):** pengajuannya tidak ada di dump.
   → **Drop** + log. *(Di jendela 12 bulan: 0.)*
6. **Baris non-hadir di hari tanpa shift pegawai:** sistem lama menyisipkan presensi **tiap hari kalender** saat approve (termasuk Sab/Ming — lihat `PresensiController::approvecuti`), sedangkan aturan baru hanya hari kerja.
   → **Drop** baris `c/s/d` di hari yang tidak punya shift di `konfigurasi_jam_kerja` pegawai itu. *(Di jendela 12 bulan: 0 — semua kebetulan hari kerja.)*
7. **`kode_jam_kerja` kapitalisasi campur:** `'Jk01'` (3 baris) → **uppercase** sebelum lookup; kalau tidak cocok setelah uppercase → `NULL` + log.
8. **Waktu:** `jam_in/out` `NULL` wajar untuk baris non-hadir; `00:00:00` hanya muncul di baris header (gugur di butir 1).
9. **String `'null'` → NULL:** 65 baris `pegawai.foto` berisi teks `'null'` (bukan SQL NULL) — dinormalkan jadi NULL supaya UI tidak menampilkan gambar rusak bernama "null". Kolom lain dicek dengan aturan yang sama.

---

## 4. Urutan eksekusi (urutan FK)

```
1. departemen            (19)
2. jam_kerja             (3)
3. cuti                  (4)          ← angka keputusan #2, bukan salin jml_hari
4. user + pegawai        (97 + 97)    ← email fiktif, password awal 123456; admin dari seeder
5. konfigurasi_jam_kerja (677)
6. pengajuan             (1.987)      ← sekalian bangun peta kode_cuti → id baru (di memori)
7. presensi 12 bulan     (10.602)     ← pakai peta dari langkah 6 + aturan §3
8. saldo_cuti init       (§5)
9. konfigurasi lokasi    (.env)       ← manual, satu kali
10. password pegawai     (semua)      ← `php artisan pegawai:set-password-default --force`
```

Jendela presensi: **365 hari ke belakang dari tanggal run** (argumen `--since=YYYY-MM-DD` untuk override). Dari dump saat ini: `>= 2025-11-19` → **10.662** baris valid → −34 baris NIP tidak 18 digit −26 duplikat = **10.602**.

**Jangan impor:** file foto (`*-in.png`, `*-out.png`), lampiran (`doc_sid`), **semua hash password lama** (pegawai maupun admin), file dump itu sendiri. Password pegawai diatur ke default `123456` sesuai keputusan QA.

---

## 5. Init `saldo_cuti` (sekali jalan, saat ETL)

Kenapa perlu: presensi hanya 12 bulan, tetapi rollover 1 Januari 2026 sudah lewat — sistem baru tidak pernah menjalankannya untuk 2024/2025. Tanpa init, `tersedia(2026)` kehilangan tabungan.

**Rumus untuk SEMUA pegawai** — termasuk yang tidak pernah memakai cuti tahunan (sisa = 12 → dapat saldo 6; inilah yang membuat total baris mendekati 98 × 2). Sumber: pengajuan `approved` jenis **C01**, karena baris presensi pra-ETL tidak lengkap — inisialisasi, **bukan** pengganti aturan #8 yang tetap dari baris presensi setelah go-live:

```
saldo_cuti(pegawai, C01, tahun_sumber = T) = min( max(12 − hariKerja(pengajuanC01approved T), 0), 6 )
   untuk T ∈ {Y-1, Y-2}         ← Y = tahun saat ETL dijalankan (jendela 2 tahun)
   hasil 0 → tidak dibuat baris
```

Hitungan dari dump (diverifikasi 2×: `--dry-run` + hitungan independen): pemakaian C01 **basis hari dengan shift** per tahun — 2024: **290** hari, 2025: **347** hari, 2026: 60 hari; **52** pegawai pernah memakai. **Angka final baris & total saldo dicetak oleh `--dry-run`** dan dipakai sebagai pembanding verifikasi (ekspektasi: **173 baris** — 2024: 87 baris / 493 hari, 2025: 86 baris / 481 hari).
`hariKerja` = hari **dengan shift** di `konfigurasi_jam_kerja` pegawai tersebut (aturan bisnis #1 `database-design.md`) — basis yang sama dengan yang akan dipakai sistem baru setelah go-live, sehingga rollover 2027 konsisten.

Hitungan awal dari dump (diverifikasi, basis shift, Y=2026): pemakaian C01 agregat — 2024: 290 hari, 2025: 347 hari, 2026: 60 hari. **Angka final baris & total saldo dicetak oleh `--dry-run`** dan dipakai sebagai pembanding verifikasi (ekspektasi kasar Y=2026: **±175 baris**, total saldo 2024 ≈ **499** hari, total saldo 2025 ≈ **487** hari).

> Idempoten: `upsert` pada `UNIQUE(id_pegawai, id_cuti, tahun_sumber)` — run ulang tidak menggandakan.

---

## 6. Keputusan kecil yang perlu QA putuskan (bisa paralel)

| # | Pertanyaan | Rekomendasi |
|---|---|---|
| A | Foto presensi 12 bln (~10 rb file) dipindah? | **Tidak.** Kolom `foto_masuk/keluar` = `NULL`; file tetap diarsipkan di server lama. Opsi lanjutan: `presensi:import-foto` terpisah → `storage/app/private/` (di luar git). |
| B | `pengajuan.lampiran` (906 file `doc_sid`)? | **Isi nama file asli** sebagai jejak "ada surat"; file di server lama; UI wajib cek keberadaan file sebelum menampilkan link. *(Alternatif: `NULL` kalau tim mau seragam dengan A.)* |
| C | 22 pengajuan `pending` (Juni 2026)? | **Import sebagai `pending`** — biar admin baru memutuskan; jangan dianggap approved. |
| D | Radius kantor `25.000` m dari data lama? | Konfirmasi ke HR — 25 km sangat longgar. Masukkan ke `.env` apa adanya dulu, mudah diubah. |

---

## 7. Bentuk command (untuk issue ETL)

Satu artisan command, mis. **`presensi:import-lama {--path=} {--since=} {--dry-run} {--force}`**:

- `--dry-run` → **tidak menulis apa pun**; mencetak: path dump, jumlah baris per tabel, daftar drop per alasan (§3), target saldo (§5), dan laporan siap-banding (§4).
- Tanpa `--dry-run` → minta konfirmasi `yes` (atau `--force`), lalu eksekusi dalam **satu transaksi per tabel** + `DB::transaction` besar per langkah 6–7.
- **Idempoten:** semua insert `upsert`/`firstOrCreate` berdasarkan business key (`kode`, `nip`, `(nip,hari)`, `kode_cuti` lama via peta, `(tgl,nip)`, `(pegawai,cuti,tahun)`). Run ulang = hasil sama.
- **Log:** `storage/logs/etl-import.log` berisi setiap baris yang di-drop beserta alasannya.
- **Dump path ditolak** bila berada di dalam direktori repo (guard: `str_starts_with(realpath($path), base_path())`) — mencegah dump tidak sengaja ter-commit.

---

## 8. Checklist verifikasi pasca-import

- [ ] `--dry-run` tanpa error, semua angka cocok §4 (atau selisihnya terjelaskan di log).
- [ ] Query integritas:
  - `pegawai` tanpa `user` = 0 · `user` tanpa `pegawai` = 0 (kecuali admin seeder)
  - `konfigurasi_jam_kerja` duplikat `(id_pegawai,hari)` = 0
  - `presensi` duplikat `(tgl_presensi,id_pegawai)` = 0
  - `pengajuan.jenis='cuti'` tanpa `id_cuti` = 0 (CHECK juga menegakkan)
  - `presensi.status<>'hadir'` tanpa `id_pengajuan` = 0
  - `saldo_cuti` duplikat `(id_pegawai,id_cuti,tahun_sumber)` = 0; `saldo <= 6`
- [ ] Angka baris sesuai target §4.
- [ ] Spot-check 3 pegawai: buka halaman histori presensi & "sisa cuti" — cocokkan dengan hitungan manual dari pengajuan lama.
- [ ] Uji satu pengajuan **baru** end-to-end (submit → approve → baris presensi terbentuk → sisa berkurang).
- [ ] `.env` berisi `PRESENSI_LAT/LONG/RADIUS` (dari `konfigurasi_lokasi`: `-2.91380858459444`, `104.69505610526285`, `25000` — lihat keputusan D).

## 9. Rollback

- Sebelum run: backup DB (`mysqldump`).
- Selama run: transaksi per langkah — gagal = langkah itu kembali utuh, run ulang aman (idempoten).
- Sesudah run: tabel hasil ETL boleh ditruncate ulang + restore backup; file dump tidak tersentuh.

## 10. Setelah ETL selesai

1. Dump lama **tetap di luar repo & di server lama** — jangan dipindah.
2. Server lama dimatikan / diarsipkan setelah UAT (issue #52).
3. Tambahkan issue lanjutan bila keputusan A/B memilih memindahkan file foto/lampiran.
