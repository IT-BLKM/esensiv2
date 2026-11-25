<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Aplikasi memakai Argon2id untuk semua password baru. Driver khusus
    | `argon2id_bcrypt` juga bisa memverifikasi hash bcrypt lama (termasuk
    | data ETL) dan akan di-rehash saat login berhasil.
    |
    | Supported drivers: "bcrypt", "argon", "argon2id", "argon2id_bcrypt"
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id_bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Tetap tersedia untuk verifikasi hash lama. Password baru tidak dibuat
    | dengan bcrypt.
    |
    */

    'bcrypt' => [
        'rounds' => (int) env('BCRYPT_ROUNDS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Parameter Argon2id. `memory` dalam KiB; 65536 = 64 MiB per verifikasi.
    | Turunkan setelah benchmark bila server memiliki RAM terbatas.
    |
    */

    'argon' => [
        'memory' => (int) env('ARGON_MEMORY', 65536),
        'time' => (int) env('ARGON_TIME', 4),
        'threads' => (int) env('ARGON_THREADS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash on Login
    |--------------------------------------------------------------------------
    |
    | Setelah login berhasil dengan hash bcrypt lama, hash otomatis diganti
    | menjadi Argon2id. Matikan hanya bila QA memilih rehash bertahap lewat
    | ganti/reset password.
    |
    */

    'rehash_on_login' => (bool) env('HASH_REHASH_ON_LOGIN', true),

];
