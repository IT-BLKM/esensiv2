<?php

namespace Database\Seeders;

use App\Models\HariLibur;
use Illuminate\Database\Seeder;

/**
 * Daftar awal hari libur nasional 2026–2027.
 *
 * Tanggal keagamaan dapat bergeser mengikuti keputusan pemerintah; admin dapat
 * mengubah/menambah lewat menu Hari Libur. Yang penting secara teknis adalah
 * tanggal tersimpan di tabel `hari_libur` dan dipakai sebagai pengecualian
 * hari kerja.
 */
class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            // 2026
            ['tanggal' => '2026-01-01', 'nama' => 'Tahun Baru 2026'],
            ['tanggal' => '2026-01-16', 'nama' => 'Isra Mikraj Nabi Muhammad SAW'],
            ['tanggal' => '2026-02-17', 'nama' => 'Tahun Baru Imlek 2577 Kongzili'],
            ['tanggal' => '2026-03-19', 'nama' => 'Hari Suci Nyepi Tahun Baru Saka 1948'],
            ['tanggal' => '2026-03-20', 'nama' => 'Hari Raya Idul Fitri 1447 H'],
            ['tanggal' => '2026-03-21', 'nama' => 'Hari Raya Idul Fitri 1447 H'],
            ['tanggal' => '2026-04-03', 'nama' => 'Wafat Isa Al Masih'],
            ['tanggal' => '2026-05-01', 'nama' => 'Hari Buruh Internasional'],
            ['tanggal' => '2026-05-14', 'nama' => 'Kenaikan Isa Al Masih'],
            ['tanggal' => '2026-05-27', 'nama' => 'Hari Raya Idul Adha 1447 H'],
            ['tanggal' => '2026-05-31', 'nama' => 'Hari Raya Waisak 2570 BE'],
            ['tanggal' => '2026-06-01', 'nama' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2026-06-27', 'nama' => 'Tahun Baru Islam 1448 H'],
            ['tanggal' => '2026-08-17', 'nama' => 'Hari Kemerdekaan Republik Indonesia'],
            ['tanggal' => '2026-08-25', 'nama' => 'Maulid Nabi Muhammad SAW'],
            ['tanggal' => '2026-12-25', 'nama' => 'Hari Raya Natal'],

            // 2027 — tanggal tetap; hari raya yang bergeser ditambahkan admin.
            ['tanggal' => '2027-01-01', 'nama' => 'Tahun Baru 2027'],
            ['tanggal' => '2027-05-01', 'nama' => 'Hari Buruh Internasional'],
            ['tanggal' => '2027-06-01', 'nama' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2027-08-17', 'nama' => 'Hari Kemerdekaan Republik Indonesia'],
            ['tanggal' => '2027-12-25', 'nama' => 'Hari Raya Natal'],
        ] as $libur) {
            HariLibur::updateOrCreate(
                ['tanggal' => $libur['tanggal']],
                ['nama' => $libur['nama']],
            );
        }
    }
}
