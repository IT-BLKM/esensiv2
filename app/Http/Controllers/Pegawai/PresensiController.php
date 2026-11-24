<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Presensi\CariPresensiAction;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    public function index(Request $request, CariPresensiAction $cari): JsonResponse
    {
        $this->authorize('viewAny', Presensi::class);

        /** @var User $user */
        $user = $request->user();

        return response()->json($cari($user)->map(fn (Presensi $p) => $this->ringkas($p))->values());
    }

    public function show(Presensi $presensi): JsonResponse
    {
        $this->authorize('view', $presensi);

        return response()->json($this->ringkas($presensi));
    }

    /**
     * Foto dan koordinat sengaja tidak dikembalikan: endpoint ini hanya
     * membuktikan batas akses, bukan menggantikan fitur histori (#33).
     *
     * @return array{id: int, id_pegawai: int, tgl_presensi: string, status: string}
     */
    private function ringkas(Presensi $presensi): array
    {
        return [
            'id' => $presensi->id,
            'id_pegawai' => $presensi->id_pegawai,
            'tgl_presensi' => $presensi->tgl_presensi,
            'status' => $presensi->status,
        ];
    }
}
