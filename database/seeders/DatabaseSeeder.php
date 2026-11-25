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
     * - pegawai@esensi.test ... pegawai5@esensi.test
     * - tanpa-pegawai@esensi.test
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            PenggunaSeeder::class,
            AktivitasSeeder::class,
        ]);
    }
}
