<?php

namespace Database\Factories;

use App\Models\HariLibur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HariLibur>
 */
class HariLiburFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->date(),
            'nama' => 'Libur '.fake()->word(),
        ];
    }
}
