<?php

namespace Database\Factories;

use App\Models\JamKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JamKerja>
 */
class JamKerjaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->regexify('[A-Z]{4}'),
            'nama' => fake()->unique()->word().' Shift',
            'awal_masuk' => '07:30:00',
            'jam_masuk' => '08:00:00',
            'akhir_masuk' => '08:30:00',
            'jam_pulang' => '17:00:00',
        ];
    }
}
