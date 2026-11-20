<?php

namespace Database\Factories;

use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Pengajuan>
 */
class PengajuanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'id_pegawai' => 1,
            'id_cuti' => 1,
            'tanggal_mulai' => Carbon::instance($tanggalMulai)->toDateString(),
            'tanggal_berakhir' => Carbon::instance($tanggalMulai)->addDays(2)->toDateString(),
            'jenis' => 'cuti',
            'keterangan' => fake()->sentence(),
            'lampiran' => null,
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ];
    }
}
