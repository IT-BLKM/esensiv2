<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePegawaiAction
{
    /**
     * Perbarui pegawai, sinkronkan nama/email user, dan ganti foto bila diunggah.
     * Password tidak pernah disentuh oleh update biasa.
     *
     * @param  array{nip: string, email: string, nama: string, jabatan: string, id_dept: int, no_hp: string, foto?: UploadedFile|null}  $data
     */
    public function __invoke(Pegawai $pegawai, array $data): Pegawai
    {
        $fotoLama = $pegawai->foto;
        $fotoBaru = $data['foto'] ?? null;
        $email = $data['email'];
        unset($data['foto'], $data['email']);

        $fotoPath = null;

        if ($fotoBaru instanceof UploadedFile) {
            $fotoPath = $fotoBaru->store('foto-pegawai', 'public');

            if (is_string($fotoPath)) {
                $data['foto'] = $fotoPath;
            }
        }

        DB::transaction(function () use ($pegawai, $data, $email): void {
            $pegawai->update($data);

            $user = $pegawai->user;

            if ($user !== null && ($user->nama !== $pegawai->nama || $user->email !== $email)) {
                $user->update([
                    'nama' => $pegawai->nama,
                    'email' => $email,
                ]);
            }
        });

        if (is_string($fotoPath) && $fotoLama !== null) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $pegawai->refresh();
    }
}
