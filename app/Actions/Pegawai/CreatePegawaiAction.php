<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreatePegawaiAction
{
    public function __construct(private readonly BuatPasswordAwalAction $buatPasswordAwal) {}

    /**
     * Buat pegawai dan akun user-nya dalam satu transaksi.
     *
     * @param  array{nip: string, email: string, nama: string, jabatan: string, id_dept: int, no_hp: string, foto?: UploadedFile|null}  $data
     * @return array{pegawai: Pegawai, passwordAwal: string}
     */
    public function __invoke(array $data): array
    {
        $foto = $data['foto'] ?? null;
        $email = $data['email'];
        unset($data['foto'], $data['email']);

        $passwordAwal = ($this->buatPasswordAwal)();

        $pegawai = DB::transaction(function () use ($data, $foto, $email, $passwordAwal): Pegawai {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $email,
                'password' => $passwordAwal,
                'role' => 'pegawai',
            ]);

            $data['user_id'] = $user->id;

            if ($foto instanceof UploadedFile) {
                $path = $foto->store('foto-pegawai', 'public');

                if (is_string($path)) {
                    $data['foto'] = $path;
                }
            }

            return Pegawai::create($data);
        });

        return [
            'pegawai' => $pegawai,
            'passwordAwal' => $passwordAwal,
        ];
    }
}
