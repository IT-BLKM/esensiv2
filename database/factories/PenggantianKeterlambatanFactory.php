<?php

namespace Database\Factories;

use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenggantianKeterlambatan>
 */
class PenggantianKeterlambatanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_presensi' => Presensi::factory(),
            'tanggal' => fake()->date(),
            'jam_mulai' => '16:00:00',
            'jam_selesai' => '16:20:00',
            'target_pulang' => '16:20:00',
            'batas_pulang' => '16:25:00',
            'menit_terlambat' => 20,
            'menit_diganti' => 20,
            'bisa_diganti' => true,
            'terpenuhi' => true,
            'catatan' => 'Terlambat 20 menit; pulang 16:20 mengganti dalam jendela 16:20–16:25.',
        ];
    }

    public function tidakTerpenuhi(): static
    {
        return $this->state(fn (): array => [
            'menit_diganti' => 0,
            'terpenuhi' => false,
            'catatan' => 'Pulang sebelum target penggantian.',
        ]);
    }

    public function tidakBisaDiganti(): static
    {
        return $this->state(fn (): array => [
            'target_pulang' => null,
            'batas_pulang' => null,
            'menit_diganti' => 0,
            'bisa_diganti' => false,
            'terpenuhi' => false,
            'catatan' => 'Terlambat lebih dari 30 menit sehingga tidak dapat diganti.',
        ]);
    }
}
