<?php

namespace Database\Seeders;

use App\Models\Cuti;
use App\Models\Departemen;
use App\Models\JamKerja;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Master data deterministik: departemen, shift, dan jenis cuti PNS.
     */
    public function run(): void
    {
        foreach ([
            ['kode' => 'UMUM', 'nama' => 'Umum'],
            ['kode' => 'KEU', 'nama' => 'Keuangan'],
            ['kode' => 'PROG', 'nama' => 'Program dan Layanan'],
            ['kode' => 'LAB', 'nama' => 'Laboratorium'],
            ['kode' => 'TU', 'nama' => 'Tata Usaha'],
        ] as $departemen) {
            Departemen::updateOrCreate(['kode' => $departemen['kode']], $departemen);
        }

        foreach ([
            [
                'kode' => 'JK01',
                'nama' => 'Senin-Kamis',
                'awal_masuk' => '07:00:00',
                'jam_masuk' => '07:30:00',
                'akhir_masuk' => '09:30:00',
                'jam_pulang' => '16:00:00',
            ],
            [
                'kode' => 'JK02',
                'nama' => 'Jumat',
                'awal_masuk' => '07:00:00',
                'jam_masuk' => '07:30:00',
                'akhir_masuk' => '09:30:00',
                'jam_pulang' => '16:30:00',
            ],
            [
                'kode' => 'JK03',
                'nama' => 'Sabtu-Minggu',
                'awal_masuk' => '06:30:00',
                'jam_masuk' => '07:00:00',
                'akhir_masuk' => '15:00:00',
                'jam_pulang' => '21:00:00',
            ],
        ] as $shift) {
            JamKerja::updateOrCreate(['kode' => $shift['kode']], $shift);
        }

        foreach ([
            ['kode' => 'C01', 'nama' => 'Cuti Tahunan', 'maks_hari' => 12, 'kuota' => 12, 'maks_bawa' => 6],
            ['kode' => 'C02', 'nama' => 'Cuti Bersalin', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0],
            ['kode' => 'C03', 'nama' => 'Cuti Alasan Penting', 'maks_hari' => 30, 'kuota' => 30, 'maks_bawa' => 0],
            ['kode' => 'C04', 'nama' => 'Cuti Besar', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0],
        ] as $cuti) {
            Cuti::updateOrCreate(['kode' => $cuti['kode']], $cuti);
        }
    }
}
