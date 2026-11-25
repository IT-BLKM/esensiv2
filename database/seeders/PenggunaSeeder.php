<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    /**
     * Akun demo dengan password "password":
     * - admin@esensi.test (admin)
     * - pegawai@esensi.test (pegawai utama)
     * - pegawai2@esensi.test s.d. pegawai5@esensi.test
     * - tanpa-pegawai@esensi.test (akun tanpa data pegawai)
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@esensi.test'],
            ['nama' => 'Administrator', 'password' => 'password', 'role' => 'admin'],
        );

        $daftar = [
            ['email' => 'pegawai@esensi.test', 'nama' => 'Budi Santoso', 'nip' => '199302032022031001', 'jabatan' => 'Staf Program', 'dept' => 'PROG', 'no_hp' => '081234567801'],
            ['email' => 'pegawai2@esensi.test', 'nama' => 'Siti Aminah', 'nip' => '199401012019012002', 'jabatan' => 'Bendahara', 'dept' => 'KEU', 'no_hp' => '081234567802'],
            ['email' => 'pegawai3@esensi.test', 'nama' => 'Ahmad Fauzi', 'nip' => '199505152020122003', 'jabatan' => 'Analis Laboratorium', 'dept' => 'LAB', 'no_hp' => '081234567803'],
            ['email' => 'pegawai4@esensi.test', 'nama' => 'Rina Wati', 'nip' => '199806202021012004', 'jabatan' => 'Staf Umum', 'dept' => 'UMUM', 'no_hp' => '081234567804'],
            ['email' => 'pegawai5@esensi.test', 'nama' => 'Joko Prasetyo', 'nip' => '199207112018011005', 'jabatan' => 'Staf Tata Usaha', 'dept' => 'TU', 'no_hp' => '081234567805'],
        ];

        foreach ($daftar as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['nama' => $data['nama'], 'password' => 'password', 'role' => 'pegawai'],
            );

            Pegawai::updateOrCreate(
                ['nip' => $data['nip']],
                [
                    'user_id' => $user->id,
                    'id_dept' => Departemen::query()->where('kode', $data['dept'])->value('id'),
                    'nama' => $data['nama'],
                    'jabatan' => $data['jabatan'],
                    'no_hp' => $data['no_hp'],
                    'foto' => null,
                ],
            );
        }

        User::updateOrCreate(
            ['email' => 'tanpa-pegawai@esensi.test'],
            ['nama' => 'Akun Tanpa Pegawai', 'password' => 'password', 'role' => 'pegawai'],
        );
    }
}
