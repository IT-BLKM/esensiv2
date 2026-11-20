<?php

namespace Database\Factories;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->numberBetween(1, 100000),
            'id_dept' => fake()->numberBetween(1, 100),
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'foto' => null,
            'no_hp' => fake()->numerify('08##########'),
        ];
    }
}
