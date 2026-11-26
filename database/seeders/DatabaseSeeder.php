<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data demo lengkap untuk menguji fitur admin dan pegawai.
     *
     * Akun (password semuanya "password"):
     * - admin@esensi.test
     * - pegawai@esensi.test ... pegawai50@esensi.test
     * - tanpa-pegawai@esensi.test
     *
     * AktivitasSeeder membuat skenario demo utama lebih dulu; RiwayatPresensiSeeder
     * mengisi sisanya sehingga total ada 50 pegawai dengan riwayat tiga bulan.
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            HariLiburSeeder::class,
        ]);

        // Jangan pernah membuat akun/demo password di produksi. Admin
        // produksi dibuat eksplisit lewat `php artisan admin:create`.
        if (app()->isProduction()) {
            return;
        }

        $this->call([
            PenggunaSeeder::class,
            AktivitasSeeder::class,
            RiwayatPresensiSeeder::class,
        ]);
    }
}
