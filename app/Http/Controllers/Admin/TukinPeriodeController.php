<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TukinPeriodeRequest;
use App\Models\Pegawai;
use App\Models\TukinPeriode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TukinPeriodeController extends Controller
{
    public function kunci(TukinPeriodeRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Pegawai::class);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        TukinPeriode::query()->updateOrCreate(
            ['tahun' => (int) $data['tahun'], 'bulan' => (int) $data['bulan']],
            [
                'terkunci' => true,
                'dikunci_oleh' => $user->id,
                'dikunci_pada' => now(),
                'dibuka_oleh' => null,
                'dibuka_pada' => null,
                'catatan' => $data['catatan'] ?? null,
            ],
        );

        return redirect()->route('admin.tukin.index', [
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
        ])->with('success', 'Periode TUKIN dikunci. Presensi dan approval pada periode ini ditolak.');
    }

    public function buka(TukinPeriodeRequest $request, TukinPeriode $tukinPeriode): RedirectResponse
    {
        $this->authorize('viewAny', Pegawai::class);

        /** @var User $user */
        $user = $request->user();

        $tukinPeriode->update([
            'terkunci' => false,
            'dibuka_oleh' => $user->id,
            'dibuka_pada' => now(),
        ]);

        return redirect()->route('admin.tukin.index', [
            'bulan' => $tukinPeriode->bulan,
            'tahun' => $tukinPeriode->tahun,
        ])->with('success', 'Kunci periode TUKIN dibuka.');
    }
}
