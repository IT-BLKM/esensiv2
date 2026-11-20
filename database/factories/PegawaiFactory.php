<?php

namespace Database\Factories;

use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

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
            'user_id' => $this->userId(),
            'id_dept' => $this->departemenId(),
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'foto' => null,
            'no_hp' => fake()->numerify('08##########'),
        ];
    }

    /**
     * Pakai user hasil factory begitu tabel `user` tersedia; sebelum PR #54
     * di-merge cukup pakai id numerik karena FK belum aktif.
     */
    private function userId(): int|Factory
    {
        if (Schema::hasTable('user')) {
            return User::factory();
        }

        return 1;
    }

    /**
     * Sama seperti userId, tapi untuk departemen (PR #55).
     */
    private function departemenId(): int|Factory
    {
        if (class_exists(Departemen::class) && Schema::hasTable('departemen')) {
            return Departemen::factory();
        }

        return 1;
    }
}
