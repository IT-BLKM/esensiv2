<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Buat pengajuan baru milik user yang sedang login.
 *
 * Pegawai, status awal, dan jejak approval tidak dipercaya dari klien: semua
 * ditentukan di server supaya tidak ada IDOR atau pengajuan langsung approved.
 */
class CreatePengajuanAction
{
    /**
     * @param  array{id_cuti?: int|string|null, tanggal_mulai: string, tanggal_berakhir: string, jenis: string, keterangan: string, lampiran?: UploadedFile|null}  $data
     */
    public function __invoke(User $user, array $data): Pengajuan
    {
        Gate::authorize('create', Pengajuan::class);

        $pegawai = $user->pegawai;

        if ($pegawai === null) {
            throw new AuthorizationException('Akun ini belum tertaut ke data pegawai.');
        }

        $lampiran = $data['lampiran'] ?? null;
        unset($data['lampiran']);

        $path = null;

        if ($lampiran instanceof UploadedFile) {
            $path = $lampiran->store('lampiran-pengajuan', 'local');
        }

        try {
            return DB::transaction(fn (): Pengajuan => Pengajuan::query()->create(array_merge($data, [
                'id_pegawai' => $pegawai->id,
                'status' => 'pending',
                'lampiran' => $path,
            ])));
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
