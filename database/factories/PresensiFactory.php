<?php

namespace Database\Factories;

use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/**
 * @extends Factory<Presensi>
 */
class PresensiFactory extends Factory
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
            'id_pengajuan' => null,
            'tgl_presensi' => fake()->date(),
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'foto_masuk' => fake()->uuid().'-in.png',
            'foto_keluar' => fake()->uuid().'-out.png',
            'koordinat_masuk' => '-2.9138085,104.6950561',
            'koordinat_keluar' => '-2.9138085,104.6950561',
            'status' => 'hadir',
        ];
    }

    /**
     * Baris cuti/sakit/dl dibuat otomatis saat pengajuan disetujui,
     * jadi perlu jejak pengajuan yang valid.
     */
    public function withPengajuan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'id_pengajuan' => $this->pengajuanId(),
        ]);
    }

    /**
     * Parent dibuat otomatis begitu tabelnya tersedia (PR #12 / #57 / #61).
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

    private function pengajuanId(): int|Factory
    {
        if (class_exists(Pengajuan::class) && Schema::hasTable('pengajuan')) {
            return Pengajuan::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }
}
