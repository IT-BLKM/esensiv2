<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cuti\HitungSisaTahunanAction;
use App\Actions\Cuti\SimpanSaldoAwalCutiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSaldoCutiRequest;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\SaldoCuti;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Input saldo awal/tabungan cuti tahunan untuk migrasi produksi.
 *
 * Halaman ini hanya mengubah saldo Y-1 dan Y-2. Pemakaian tahun berjalan
 * tetap berasal dari baris presensi/pengajuan approved.
 */
class SaldoCutiController extends Controller
{
    public function index(Request $request, HitungSisaTahunanAction $hitungSisaTahunan): View
    {
        $this->authorize('viewAny', Cuti::class);

        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $cuti = $this->cutiTahunan();
        $kataKunci = $request->string('q')->toString();

        $pegawai = Pegawai::query()
            ->when($kataKunci !== '', function ($query) use ($kataKunci): void {
                $query->where(function ($sub) use ($kataKunci): void {
                    $sub->where('nama', 'like', '%'.$kataKunci.'%')
                        ->orWhere('nip', 'like', '%'.$kataKunci.'%');
                });
            })
            ->orderBy('nama')
            ->get(['id', 'nip', 'nama']);

        $saldo = SaldoCuti::query()
            ->where('id_cuti', $cuti->id)
            ->whereIn('tahun_sumber', [$tahun - 1, $tahun - 2])
            ->get()
            ->groupBy('id_pegawai');

        $baris = $pegawai->map(function (Pegawai $item) use ($saldo, $hitungSisaTahunan, $cuti, $tahun): array {
            $ringkas = $hitungSisaTahunan($item, $cuti, $tahun);
            $milik = $saldo->get($item->id) ?? collect();
            $barisTahunLalu = $milik->firstWhere('tahun_sumber', $tahun - 1);
            $barisDuaTahunLalu = $milik->firstWhere('tahun_sumber', $tahun - 2);

            return [
                'pegawai' => $item,
                'saldo_tahun_lalu' => $barisTahunLalu instanceof SaldoCuti ? (int) $barisTahunLalu->saldo : 0,
                'saldo_dua_tahun_lalu' => $barisDuaTahunLalu instanceof SaldoCuti ? (int) $barisDuaTahunLalu->saldo : 0,
                'terpakai' => $ringkas['terpakai'],
                'sisaKuota' => $ringkas['sisaKuota'],
                'totalTersedia' => $ringkas['totalTersedia'],
            ];
        });

        return view('admin.saldo-cuti.index', [
            'baris' => $baris,
            'kataKunci' => $kataKunci,
            'tahun' => $tahun,
            'tahunLalu' => $tahun - 1,
            'duaTahunLalu' => $tahun - 2,
            'cuti' => $cuti,
        ]);
    }

    public function edit(Pegawai $pegawai, HitungSisaTahunanAction $hitungSisaTahunan): View
    {
        $this->authorize('updateSaldo', Cuti::class);

        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $cuti = $this->cutiTahunan();
        $saldo = SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $cuti->id)
            ->whereIn('tahun_sumber', [$tahun - 1, $tahun - 2])
            ->pluck('saldo', 'tahun_sumber');

        return view('admin.saldo-cuti.edit', [
            'pegawai' => $pegawai,
            'cuti' => $cuti,
            'tahun' => $tahun,
            'tahunLalu' => $tahun - 1,
            'duaTahunLalu' => $tahun - 2,
            'saldoTahunLalu' => (int) ($saldo[$tahun - 1] ?? 0),
            'saldoDuaTahunLalu' => (int) ($saldo[$tahun - 2] ?? 0),
            'ringkas' => $hitungSisaTahunan($pegawai, $cuti, $tahun),
        ]);
    }

    public function update(
        UpdateSaldoCutiRequest $request,
        Pegawai $pegawai,
        SimpanSaldoAwalCutiAction $simpan,
    ): RedirectResponse {
        $this->authorize('updateSaldo', Cuti::class);

        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $simpan($pegawai, $this->cutiTahunan(), $tahun, $request->validated());

        return redirect()->route('admin.saldo-cuti.index')
            ->with('success', "Saldo awal cuti {$pegawai->nama} berhasil disimpan.");
    }

    private function cutiTahunan(): Cuti
    {
        return Cuti::query()->where('kode', Cuti::KODE_TAHUNAN)->firstOrFail();
    }
}
