<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\RingkasLaporanAction;
use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function show(
        Request $request,
        Pegawai $pegawai,
        RingkasLaporanAction $ringkas,
    ): JsonResponse {
        $this->authorize('view', $pegawai);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id_pegawai' => $pegawai->id,
            'jumlah_presensi' => $ringkas($user, $pegawai)->count(),
        ]);
    }
}
