# Migrasi Data Awal Produksi (Manual)

Panduan ini untuk go-live **1 Desember 2026** ketika database aplikasi lama tidak
bisa dipakai langsung. Semua data historis dimasukkan admin lewat aplikasi.

## Yang sudah tersedia

| Kebutuhan | Tempat |
|---|---|
| Master pegawai, departemen, shift, jenis cuti | menu admin / seeder master |
| Hari libur nasional | menu **Hari Libur** |
| Saldo awal/tabungan cuti tahunan (Y-1, Y-2) | menu **Saldo Cuti** |
| Cuti/sakit/DL/izin historis | menu **Pengajuan** → buat → setujui |
| Kuota dan pemakaian cuti | dihitung otomatis dari presensi + pengajuan approved |

Hari libur dan akhir pekan **tidak** dihitung sebagai hari kerja. Approval
pengajuan hanya membuat baris presensi pada hari kerja.

## Urutan go-live

1. **Deploy dan migrasi skema**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   php artisan admin:create
   ```
   Di produksi `db:seed` hanya mengisi master data dan hari libur; akun demo
   tidak dibuat.

2. **Lengkapi master**
   - Periksa **Pegawai**, **Departemen**, **Jam Kerja**, dan **Cuti**.
   - Periksa **Hari Libur**. Tambah/ubah tanggal yang bergeser sesuai SKB
     pemerintah.

3. **Isi saldo awal cuti tahunan**
   - Buka **Saldo Cuti**.
   - Untuk setiap pegawai, isi tabungan tahun lalu (2025) dan dua tahun lalu
     (2024). Maksimal 6 hari per tahun sumber.
   - Angka ini adalah tabungan, bukan pemakaian. Pemakaian 2026 dihitung dari
     pengajuan yang disetujui.

4. **Input cuti/sakit/DL/izin historis**
   - Buka **Pengajuan** → **Buat pengajuan pegawai**.
   - Pilih pegawai, jenis, dan rentang tanggal sesuai catatan lama.
   - Centang **Setujui langsung** bila data historis harus langsung approved,
     atau simpan dulu lalu buka detail dan tekan **Setujui**.
   - Approval membuat baris presensi untuk setiap hari kerja dalam rentang.
     Sabtu/Minggu dan hari libur otomatis dilewati.
   - Untuk cuti tahunan gunakan **C01**. Untuk cuti alasan penting gunakan
     **C03**. Untuk cuti besar **C04**; input riwayat 5 tahun bila ada karena
     syarat C04 memeriksa riwayat.
   - Sakit lebih dari 3 hari kerja dan C03 membutuhkan lampiran.
   - DL penuh membuat baris `dl` otomatis. DL setengah hari tidak membuat baris
     otomatis karena pegawai harus absen sendiri; untuk data historis gunakan
     DL penuh bila tidak ada data absen yang bisa direkonstruksi.

5. **Verifikasi**
   - Dashboard pegawai: sisa cuti tahunan = 12 + tabungan − pemakaian.
   - Laporan bulanan: baris cuti/sakit/DL muncul dan hari libur tidak menjadi
     alpa.
   - Rekap keterlambatan tetap kosong untuk hari yang belum punya baris
     presensi.
   - Setelah 1 Januari 2027, command rollover otomatis membuat tabungan 2026.

## Pergantian tahun

- Kuota cuti tahunan dihitung per tahun kalender; tidak ada angka yang perlu
  di-reset manual.
- Command `cuti:rollover-tahunan` berjalan otomatis setiap 1 Januari pukul
  00.05 WIB dan juga dipanggil malas saat sisa cuti dibuka, sehingga tabungan
  tahun sebelumnya tetap terbentuk bila cron sempat tidak jalan.
- Tabungan lebih dari dua tahun otomatis diabaikan (hangus) oleh perhitungan.
- **Hari libur tidak dibuat otomatis untuk tahun baru.** Admin perlu menambah
  hari libur tahun berikutnya lewat menu **Hari Libur**. Halaman tersebut
  memberi peringatan bila tahun berjalan belum punya hari libur.
- Laporan dan filter tahun mengikuti data presensi; tidak perlu mengubah kode.

## Batasan penting

- **Presensi harian biasa tidak ikut dimasukkan** oleh alur cuti/sakit/DL.
  Jika database lama tidak bisa dipakai dan laporan presensi harian Jan–Nov
  2026 tetap dibutuhkan, data itu harus diimpor terpisah (ETL/CSV) atau
  direkonstruksi manual. Aplikasi saat ini belum menyediakan form input
  presensi harian massal dari admin.
- **Tidak ada impor massal** untuk pengajuan. Satu pengajuan mewakili satu
  rentang tanggal; untuk puluhan pegawai, input dilakukan bertahap.
- Jika saldo awal diisi setelah pengajuan historis, angka saldo tidak berubah
  otomatis; perbaiki lewat menu **Saldo Cuti**.
- Hari libur yang ditambahkan setelah pengajuan disetujui tidak menghapus baris
  presensi yang sudah dibuat. Batalkan lalu setujui ulang pengajuan bila perlu.
