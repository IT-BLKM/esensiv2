<?php

namespace Database\Seeders;

use App\Models\Cuti;
use App\Models\Departemen;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\SaldoCuti;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const ADMIN_EMAIL = 'admin@esensi.test';

    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdmin();

        // Bagian ini aktif setelah migration pada PR #54, #55, #57, #60,
        // #12, #59, #15, dan #11 di-merge. Tiap bagian dijaga supaya
        // migrate:fresh --seed tetap sukses sebelum PR-PR itu tersedia.
        $departemen = $this->seedDepartemen();
        $jamKerja = $this->seedJamKerja();
        $pegawai = $this->seedPegawai($departemen);
        $cuti = $this->seedCuti();

        $this->seedKonfigurasiJamKerja($pegawai, $jamKerja);
        $this->seedSaldoCuti($pegawai, $cuti);
    }

    /**
     * Admin tetap dibuat walau migration bisnis belum tersedia.
     */
    private function seedAdmin(): void
    {
        if (Schema::hasTable('user')) {
            User::factory()->create([
                'nama' => 'Administrator',
                'email' => self::ADMIN_EMAIL,
                'role' => 'admin',
            ]);

            return;
        }

        User::factory()->create([
            'name' => 'Administrator',
            'email' => self::ADMIN_EMAIL,
        ]);
    }

    private function seedDepartemen(): Collection
    {
        if (! class_exists(Departemen::class) || ! Schema::hasTable('departemen')) {
            return collect();
        }

        return Departemen::factory()->count(5)->create();
    }

    private function seedJamKerja(): Collection
    {
        if (! class_exists(JamKerja::class) || ! Schema::hasTable('jam_kerja')) {
            return collect();
        }

        return JamKerja::factory()->count(2)->sequence(
            ['kode' => 'PAGI', 'nama' => 'Shift Pagi'],
            ['kode' => 'SORE', 'nama' => 'Shift Siang'],
        )->create();
    }

    private function seedPegawai(Collection $departemen): Collection
    {
        if (! class_exists(Pegawai::class) || ! Schema::hasTable('pegawai')) {
            return collect();
        }

        return User::factory()->count(5)->create()->map(function (User $user) use ($departemen): Pegawai {
            return Pegawai::factory()->create([
                'user_id' => $user->id,
                'id_dept' => $departemen->isNotEmpty() ? $departemen->random()->id : 1,
                'nama' => $user->nama ?? $user->name ?? 'Pegawai',
            ]);
        });
    }

    private function seedCuti(): Collection
    {
        if (! class_exists(Cuti::class) || ! Schema::hasTable('cuti')) {
            return collect();
        }

        return Cuti::factory()->sequence(
            ['kode' => 'C01', 'nama' => 'Cuti Tahunan', 'maks_hari' => 12, 'kuota' => 12, 'maks_bawa' => 6],
            ['kode' => 'C02', 'nama' => 'Cuti Bersalin', 'maks_hari' => 90, 'kuota' => null, 'maks_bawa' => 0],
            ['kode' => 'C03', 'nama' => 'Cuti Alasan Penting', 'maks_hari' => null, 'kuota' => null, 'maks_bawa' => 0],
            ['kode' => 'C04', 'nama' => 'Cuti Besar', 'maks_hari' => 90, 'kuota' => null, 'maks_bawa' => 0],
        )->create();
    }

    private function seedKonfigurasiJamKerja(Collection $pegawai, Collection $jamKerja): void
    {
        if (! class_exists(KonfigurasiJamKerja::class) || ! Schema::hasTable('konfigurasi_jam_kerja')) {
            return;
        }

        if ($pegawai->isEmpty() || $jamKerja->isEmpty()) {
            return;
        }

        foreach ($pegawai as $pegawaiItem) {
            foreach (self::DAYS as $hari) {
                KonfigurasiJamKerja::factory()->create([
                    'id_pegawai' => $pegawaiItem->id,
                    'id_jam_kerja' => $jamKerja->first()->id,
                    'hari' => $hari,
                ]);
            }
        }
    }

    private function seedSaldoCuti(Collection $pegawai, Collection $cuti): void
    {
        if (! class_exists(SaldoCuti::class) || ! Schema::hasTable('saldo_cuti')) {
            return;
        }

        if ($pegawai->isEmpty() || $cuti->isEmpty()) {
            return;
        }

        $tahunBerjalan = now()->year;
        $cutiTahunan = $cuti->firstWhere('kode', 'C01');

        foreach ($pegawai as $pegawaiItem) {
            foreach ($cuti as $cutiItem) {
                SaldoCuti::factory()->create([
                    'id_pegawai' => $pegawaiItem->id,
                    'id_cuti' => $cutiItem->id,
                    'tahun_sumber' => $tahunBerjalan,
                    'saldo' => 0,
                ]);
            }

            // Sisa kuota tahun sebelumnya ikut terbawa (jendela 2 tahun).
            if ($cutiTahunan !== null && $cutiTahunan->maks_bawa > 0) {
                SaldoCuti::factory()->create([
                    'id_pegawai' => $pegawaiItem->id,
                    'id_cuti' => $cutiTahunan->id,
                    'tahun_sumber' => $tahunBerjalan - 1,
                    'saldo' => $cutiTahunan->maks_bawa,
                ]);
            }
        }
    }
}
