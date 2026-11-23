<?php

namespace Database\Factories;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/**
 * @extends Factory<SaldoCuti>
 */
class SaldoCutiFactory extends Factory
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
            'id_cuti' => Cuti::factory(),
            'tahun_sumber' => 2026,
            'saldo' => fake()->numberBetween(0, 6),
        ];
    }

    /**
     * Pegawai dibuat otomatis begitu tabelnya tersedia (PR #12). Sebelumnya
     * cukup id unik karena FK belum aktif.
     */
    private function pegawaiId(): int|Factory
    {
        if (class_exists(Pegawai::class) && Schema::hasTable('pegawai')) {
            return Pegawai::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }
}
