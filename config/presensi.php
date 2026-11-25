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

];
