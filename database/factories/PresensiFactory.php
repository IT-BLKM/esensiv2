<?php

namespace Database\Factories;

use App\Models\Presensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presensi>
 */
class PresensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pegawai' => 1,
            'id_jam_kerja' => 1,
            'id_pengajuan' => null,
            'tgl_presensi' => fake()->date(),
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'foto_masuk' => fake()->uuid().'-in.png',
            'foto_keluar' => fake()->uuid().'-out.png',
            'koordinat_masuk' => '-2.9138085,104.6950561',
            'koordinat_keluar' => '-2.9138085,104.6950561',
            'status' => 'hadir',
        ];
    }
}
