<?php

namespace Database\Factories;

use App\Models\Cuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuti>
 */
class CutiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('C##'),
            'nama' => fake()->randomElement([
                'Cuti Tahunan',
                'Cuti Bersalin',
                'Cuti Alasan Penting',
                'Cuti Besar',
            ]),
            'maks_hari' => fake()->numberBetween(1, 90),
            'kuota' => fake()->numberBetween(1, 24),
            'maks_bawa' => fake()->numberBetween(0, 6),
        ];
    }
}
