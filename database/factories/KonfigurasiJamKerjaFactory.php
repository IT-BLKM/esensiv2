<?php

namespace Database\Factories;

use App\Models\KonfigurasiJamKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KonfigurasiJamKerja>
 */
class KonfigurasiJamKerjaFactory extends Factory
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
            'hari' => 'Senin',
        ];
    }
}
