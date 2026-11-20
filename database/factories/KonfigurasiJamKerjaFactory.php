<?php

namespace Database\Factories;

use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

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
            'id_pegawai' => $this->pegawaiId(),
            'id_jam_kerja' => $this->jamKerjaId(),
            'hari' => 'Senin',
        ];
    }

    /**
     * Parent dibuat otomatis begitu tabelnya tersedia (PR #12 / #57).
     */
    private function pegawaiId(): int|Factory
    {
        if (class_exists(Pegawai::class) && Schema::hasTable('pegawai')) {
            return Pegawai::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }

    private function jamKerjaId(): int|Factory
    {
        if (class_exists(JamKerja::class) && Schema::hasTable('jam_kerja')) {
            return JamKerja::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }
}
