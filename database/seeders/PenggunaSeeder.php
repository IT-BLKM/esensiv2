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
     * - pegawai2@esensi.test s.d. pegawai50@esensi.test
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

        $namaTambahan = [
            'Adi Nugroho', 'Agus Setiawan', 'Ani Lestari', 'Arif Hidayat', 'Ayu Puspita',
            'Bagus Pratama', 'Bayu Saputra', 'Citra Dewi', 'Dedi Kurniawan', 'Dewi Anggraini',
            'Dimas Ramadhan', 'Dinda Maharani', 'Eko Prasetyo', 'Endang Susanti', 'Fajar Nugraha',
            'Fitri Handayani', 'Gilang Permana', 'Hana Safitri', 'Hendra Wijaya', 'Indah Permatasari',
            'Intan Sari', 'Irfan Maulana', 'Kartika Sari', 'Lestari Ningsih', 'Lukman Hakim',
            'Maya Sari', 'Miko Ardiansyah', 'Nanda Putra', 'Nia Kurniasih', 'Nur Aisyah',
            'Rahmat Hidayat', 'Rani Oktaviani', 'Rudi Hartono', 'Sari Wulandari', 'Sigit Purnomo',
            'Sri Rahayu', 'Taufik Hidayat', 'Tri Wahyuni', 'Umi Kalsum', 'Wahyu Setiadi',
            'Wulan Sari', 'Yudi Santoso', 'Yusuf Maulana', 'Zahra Amelia', 'Zainal Abidin',
        ];

        $jabatan = [
            'Staf Administrasi', 'Analis Laboratorium', 'Bendahara', 'Pranata Komputer',
            'Perawat', 'Dokter Umum', 'Staf Program', 'Arsiparis', 'Petugas Keamanan',
            'Cleaning Service',
        ];

        $departemen = ['UMUM', 'KEU', 'PROG', 'LAB', 'TU'];

        foreach ($namaTambahan as $index => $nama) {
            $nomor = $index + 6;

            $daftar[] = [
                'email' => "pegawai{$nomor}@esensi.test",
                'nama' => $nama,
                'nip' => sprintf(
                    '19%02d%02d%02d%04d%02d%04d',
                    70 + (($index * 7) % 30),
                    1 + ($index % 12),
                    1 + ($index % 28),
                    2000 + ($index % 21),
                    1 + ($index % 12),
                    $nomor,
                ),
                'jabatan' => $jabatan[$index % count($jabatan)],
                'dept' => $departemen[$index % count($departemen)],
                'no_hp' => '08123456'.str_pad((string) (7800 + $index), 4, '0', STR_PAD_LEFT),
            ];
        }

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
