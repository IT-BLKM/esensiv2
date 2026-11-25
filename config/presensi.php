<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Kantor
    |--------------------------------------------------------------------------
    |
    | Koordinat dan radius yang dipakai untuk memvalidasi presensi. Nilai
    | dikonfigurasi lewat .env agar tiap lingkungan bisa berbeda tanpa
    | mengubah kode, dan hanya-baca di aplikasi — ubahnya lewat .env lalu
    | `php artisan config:clear`/restart server (issue #30).
    |
    | Satuan:
    |   latitude  — derajat desimal, mis. -6.2088 (lintang, minus = selatan)
    |   longitude — derajat desimal, mis. 106.8456 (bujur, timur positif)
    |   radius    — METER (25000 = 25 km)
    |
    | Default di bawah dipakai hanya saat variabel tidak ada di .env:
    | radius 25 km sengaja longgar supaya development lokal tidak perlu
    | titik GPS sungguhan. Produksi wajib mengisi nilai sendiri di .env.
    |
    */

    'latitude' => (string) env('PRESENSI_LAT', '-2.91380858459444'),

    'longitude' => (string) env('PRESENSI_LONG', '104.69505610526285'),

    'radius' => (int) env('PRESENSI_RADIUS', 25000),

    /*
    |--------------------------------------------------------------------------
    | Batas Keterlambatan
    |--------------------------------------------------------------------------
    |
    | Batas masuk TIDAK di-hardcode di sini: setiap baris presensi dibandingkan
    | dengan `jam_masuk` shift-nya sendiri (tabel jam_kerja), sesuai jadwal
    | pegawai pada hari itu. Nilai `07:30` pada sistem lama tidak dipakai lagi.
    |
    | `kode_jam_kerja_diabaikan` berisi kode shift yang tidak dihitung terlambat
    | — data lama memakai JK03 untuk lembur/akhir pekan. Pisahkan dengan koma
    | lewat .env; kosongkan bila semua shift harus dihitung (issue #34).
    |
    */

    'kode_jam_kerja_diabaikan' => array_values(array_filter(array_map(
        static fn (string $kode): string => strtoupper(trim($kode)),
        explode(',', (string) env('PRESENSI_KODE_JAM_KERJA_DIABAIKAN', 'JK03')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Zona Waktu Absensi
    |--------------------------------------------------------------------------
    |
    | Tanggal absensi ("hari ini", "bulan ini") dihitung pada zona waktu kantor,
    | bukan zona waktu aplikasi. `config/app.php` masih UTC; sistem lama memakai
    | Asia/Jakarta. Tanpa nilai ini, dashboard bisa menampilkan tanggal kemarin
    | pada pukul 00:00–07:00 WIB (issue #34).
    |
    */

    'zona_waktu' => (string) env('PRESENSI_ZONA_WAKTU', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Identitas Kantor untuk Laporan
    |--------------------------------------------------------------------------
    |
    | Dipakai header PDF/Excel laporan presensi. Dapat diubah lewat .env tanpa
    | menyentuh view. Default mengikuti dokumen laporan lama.
    |
    */

    'nama_kantor' => (string) env(
        'PRESENSI_NAMA_KANTOR',
        'Balai Laboratorium Kesehatan Masyarakat Palembang',
    ),

    'alamat_kantor' => (string) env(
        'PRESENSI_ALAMAT_KANTOR',
        'Jl. SMB II No. 55, KM. 11 Alang-alang Lebar, Palembang 30154',
    ),

];
