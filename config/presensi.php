<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Kantor
    |--------------------------------------------------------------------------
    |
    | Koordinat dan radius yang dipakai untuk memvalidasi presensi. Nilai
    | dikonfigurasi lewat .env agar tiap lingkungan bisa berbeda tanpa
    | mengubah kode.
    |
    */

    'latitude' => (string) env('PRESENSI_LAT', '-2.91380858459444'),

    'longitude' => (string) env('PRESENSI_LONG', '104.69505610526285'),

    'radius' => (int) env('PRESENSI_RADIUS', 25000),

];
