<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdatePengajuanAction
{
    /**
     * Ubah pengajuan hanya setelah policy memeriksa kepemilikan.
     * Field owner/status/approval sengaja tidak masuk $data.
     *
     * @param  array{tanggal_mulai: string, tanggal_berakhir: string, jenis: string, keterangan: string, mode_dl?: string|null, id_cuti?: int|null, lampiran?: UploadedFile|string|null}  $data
     */
    public function __invoke(User $user, Pengajuan $pengajuan, array $data): Pengajuan
    {
        Gate::forUser($user)->authorize('update', $pengajuan);

        // Penanda haji hanya dipakai untuk validasi kelayakan C04, bukan kolom DB.
        unset($data['alasan_haji']);

        $lampiranBaru = $data['lampiran'] ?? null;
        $lampiranLama = $pengajuan->lampiran;
        $pathBaru = null;

        if ($lampiranBaru instanceof UploadedFile) {
            $pathBaru = $lampiranBaru->store('lampiran-pengajuan', 'local');

            if (! is_string($pathBaru)) {
                throw new RuntimeException('Lampiran pengajuan gagal disimpan.');
            }

            $data['lampiran'] = $pathBaru;
        } elseif (! is_string($lampiranBaru) || $lampiranBaru === '') {
            unset($data['lampiran']);
        }

        try {
            $pengajuan->update($data);
        } catch (Throwable $exception) {
            if ($pathBaru !== null) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }

        if ($pathBaru !== null && is_string($lampiranLama) && $lampiranLama !== '') {
            Storage::disk('local')->delete($lampiranLama);
        }

        return $pengajuan->refresh();
    }
}
