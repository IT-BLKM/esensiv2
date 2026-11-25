<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Presensi\PresensiException;
use App\Actions\Presensi\StorePresensiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePresensiRequest;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class StorePresensiController extends Controller
{
    /**
     * Endpoint absen masuk/pulang (issue #32). Seluruh validasi bisnis ada di
     * StorePresensiAction; controller hanya menerjemahkan hasil/kesalahan ke
     * JSON terstruktur.
     */
    public function __invoke(StorePresensiRequest $request, StorePresensiAction $store): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        if ($pegawai === null) {
            return response()->json([
                'status' => 'error',
                'type' => 'pegawai',
                'message' => 'Akun ini belum tertaut ke data pegawai, jadi presensi tidak bisa dicatat.',
            ], 422);
        }

        try {
            $hasil = $store($pegawai, $request->validated());
        } catch (PresensiException $error) {
            return response()->json([
                'status' => 'error',
                'type' => $error->type,
                'message' => $error->getMessage(),
            ], $error->status);
        }

        $presensi = $hasil['presensi'];
        $jenis = $hasil['jenis'];
        $jam = $this->formatJam($jenis === 'masuk' ? $presensi->jam_masuk : $presensi->jam_keluar);

        return response()->json([
            'status' => 'success',
            'type' => $jenis,
            'message' => $jenis === 'masuk'
                ? "Absen masuk tercatat pukul {$jam}."
                : "Absen pulang tercatat pukul {$jam}.",
            'data' => $this->ringkas($presensi),
        ], $jenis === 'masuk' ? 201 : 200);
    }

    /**
     * @return array{id: int, tgl_presensi: string, status: string, jam_masuk: string|null, jam_keluar: string|null}
     */
    private function ringkas(Presensi $presensi): array
    {
        return [
            'id' => $presensi->id,
            'tgl_presensi' => $presensi->tgl_presensi,
            'status' => $presensi->status,
            'jam_masuk' => $this->formatJam($presensi->jam_masuk),
            'jam_keluar' => $this->formatJam($presensi->jam_keluar),
        ];
    }

    private function formatJam(?string $jam): ?string
    {
        return $jam === null ? null : Carbon::parse($jam)->format('H:i');
    }
}
