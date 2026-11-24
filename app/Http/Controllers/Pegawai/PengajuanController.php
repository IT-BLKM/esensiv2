<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Pengajuan\CariPengajuanAction;
use App\Actions\Pengajuan\DeletePengajuanAction;
use App\Actions\Pengajuan\UpdatePengajuanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePengajuanRequest;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PengajuanController extends Controller
{
    public function index(Request $request, CariPengajuanAction $cari): JsonResponse
    {
        $this->authorize('viewAny', Pengajuan::class);

        /** @var User $user */
        $user = $request->user();

        return response()->json($cari($user)->map(fn (Pengajuan $p) => $this->ringkas($p))->values());
    }

    public function show(Pengajuan $pengajuan): JsonResponse
    {
        $this->authorize('view', $pengajuan);

        return response()->json($this->ringkas($pengajuan));
    }

    public function edit(Pengajuan $pengajuan): JsonResponse
    {
        $this->authorize('update', $pengajuan);

        return response()->json($this->ringkas($pengajuan));
    }

    public function update(
        UpdatePengajuanRequest $request,
        Pengajuan $pengajuan,
        UpdatePengajuanAction $update,
    ): JsonResponse {
        $this->authorize('update', $pengajuan);

        /** @var User $user */
        $user = $request->user();

        return response()->json($this->ringkas($update($user, $pengajuan, $request->validated())));
    }

    public function destroy(
        Request $request,
        Pengajuan $pengajuan,
        DeletePengajuanAction $delete,
    ): Response {
        $this->authorize('delete', $pengajuan);

        /** @var User $user */
        $user = $request->user();
        $delete($user, $pengajuan);

        return response()->noContent();
    }

    /**
     * @return array{id: int, id_pegawai: int, jenis: string, status: string}
     */
    private function ringkas(Pengajuan $pengajuan): array
    {
        return [
            'id' => $pengajuan->id,
            'id_pegawai' => $pengajuan->id_pegawai,
            'jenis' => $pengajuan->jenis,
            'status' => $pengajuan->status,
        ];
    }
}
