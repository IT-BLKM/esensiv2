<?php

namespace Database\Factories;

use App\Models\Cuti;
use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'id_pegawai' => 1,
            'id_cuti' => Cuti::factory(),
            'tahun_sumber' => 2026,
            'saldo' => fake()->numberBetween(0, 6),
        ];
    }
}
