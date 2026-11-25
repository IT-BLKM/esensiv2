<?php

namespace Database\Factories;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * @extends Factory<Pengajuan>
 */
class PengajuanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'id_pegawai' => $this->pegawaiId(),
            'id_cuti' => $this->cutiId(),
            'tanggal_mulai' => Carbon::instance($tanggalMulai)->toDateString(),
            'tanggal_berakhir' => Carbon::instance($tanggalMulai)->addDays(2)->toDateString(),
            'jenis' => 'cuti',
            'keterangan' => fake()->sentence(),
            'lampiran' => null,
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ]);
    }

    public function approvedBy(User $admin): static
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => null,
        ]);
    }

    public function rejectedBy(User $admin, string $catatan = 'Ditolak.'): static
    {
        return $this->state(fn (): array => [
            'status' => 'rejected',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => $catatan,
        ]);
    }

    public function sakit(): static
    {
        return $this->state(fn (): array => [
            'id_cuti' => null,
            'jenis' => 'sakit',
            'mode_dl' => null,
        ]);
    }

    public function dl(string $mode = 'full'): static
    {
        return $this->state(fn (): array => [
            'id_cuti' => null,
            'jenis' => 'dl',
            'mode_dl' => $mode,
        ]);
    }

    /**
     * Parent dibuat otomatis begitu tabelnya tersedia (PR #12 / #60).
     */
    private function pegawaiId(): int|Factory
    {
        if (class_exists(Pegawai::class) && Schema::hasTable('pegawai')) {
            return Pegawai::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }

    private function cutiId(): int|Factory
    {
        if (class_exists(Cuti::class) && Schema::hasTable('cuti')) {
            return Cuti::factory();
        }

        return fake()->unique()->numberBetween(1, 100000);
    }
}
