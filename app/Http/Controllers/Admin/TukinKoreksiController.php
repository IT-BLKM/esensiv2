<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTukinKoreksiRequest;
use App\Models\Pegawai;
use App\Models\TukinKoreksi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TukinKoreksiController extends Controller
{
    public function store(StoreTukinKoreksiRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Pegawai::class);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        TukinKoreksi::query()->create([
            'id_pegawai' => (int) $data['id_pegawai'],
            'tahun' => (int) $data['tahun'],
            'bulan' => (int) $data['bulan'],
            'persen' => (float) $data['persen'],
            'alasan' => (string) $data['alasan'],
            'dibuat_oleh' => $user->id,
        ]);

        return redirect()->route('admin.tukin.index', [
            'id_pegawai' => $data['id_pegawai'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
        ])->with('success', 'Koreksi TUKIN berhasil dicatat.');
    }
}
