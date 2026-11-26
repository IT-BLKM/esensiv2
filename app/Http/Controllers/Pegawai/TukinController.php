<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Tukin\AmbilDetailTukinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TukinPegawaiRequest;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class TukinController extends Controller
{
    public function __invoke(TukinPegawaiRequest $request, AmbilDetailTukinAction $ambilDetail): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $filter = $request->validated();
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $bulan = $request->query->has('bulan')
            ? (isset($filter['bulan']) ? (int) $filter['bulan'] : null)
            : $sekarang->month;
        $detail = $pegawai === null ? null : $ambilDetail($pegawai, $tahun, $bulan);
        $labelPeriode = $bulan === null
            ? (string) $tahun
            : CarbonImmutable::create($tahun, $bulan, 1)
                ->locale('id')
                ->translatedFormat('F Y');

        return view('pegawai.tukin', [
            'detail' => $detail,
            'pegawai' => $pegawai,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'labelPeriode' => $labelPeriode,
            'terkunci' => $bulan !== null && TukinPeriode::terkunci($tahun, $bulan),
            'pilihanBulan' => collect(range(1, 12))
                ->mapWithKeys(fn (int $item): array => [
                    $item => CarbonImmutable::create(2000, $item, 1)->locale('id')->translatedFormat('F'),
                ])
                ->all(),
            'pilihanTahun' => range($sekarang->year, max(2000, $sekarang->year - 4)),
        ]);
    }
}
