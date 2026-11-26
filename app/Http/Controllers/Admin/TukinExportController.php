<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\CetakPdfTukinAction;
use App\Actions\Laporan\CetakSlipTukinAction;
use App\Actions\Laporan\RekapTukinSemuaPegawaiAction;
use App\Actions\Laporan\TulisExcelTukinAction;
use App\Actions\Tukin\AmbilDetailTukinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TukinRequest;
use App\Models\Pegawai;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TukinExportController extends Controller
{
    public function excel(
        TukinRequest $request,
        RekapTukinSemuaPegawaiAction $rekap,
        AmbilDetailTukinAction $ambilDetail,
        TulisExcelTukinAction $tulis,
    ): BinaryFileResponse {
        [$user, $pegawai, $tahun, $bulan, $labelPeriode, $kodePeriode] = $this->siapkan($request);

        if ($pegawai === null) {
            $data = $rekap($user, $tahun, $bulan);
            $path = $tulis->semua($data);
            $namaFile = "rekap-tukin-semua-{$kodePeriode}.xlsx";
        } else {
            $data = $ambilDetail($pegawai, $tahun, $bulan);
            $path = $tulis->pegawai($data, $pegawai, $labelPeriode);
            $namaFile = 'laporan-tukin-'.Str::slug($pegawai->nama)."-{$kodePeriode}.xlsx";
        }

        return response()->download($path, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function pdf(
        TukinRequest $request,
        RekapTukinSemuaPegawaiAction $rekap,
        AmbilDetailTukinAction $ambilDetail,
        CetakPdfTukinAction $cetak,
    ): Response {
        [$user, $pegawai, $tahun, $bulan, $labelPeriode, $kodePeriode] = $this->siapkan($request);

        if ($pegawai === null) {
            $data = $rekap($user, $tahun, $bulan);
            $pdf = $cetak->semua($data);
            $namaFile = "rekap-tukin-semua-{$kodePeriode}.pdf";
        } else {
            $data = $ambilDetail($pegawai, $tahun, $bulan);
            $pdf = $cetak->pegawai($data, $pegawai, $labelPeriode);
            $namaFile = 'laporan-tukin-'.Str::slug($pegawai->nama)."-{$kodePeriode}.pdf";
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
        ]);
    }

    public function slip(
        TukinRequest $request,
        Pegawai $pegawai,
        AmbilDetailTukinAction $ambilDetail,
        CetakSlipTukinAction $cetak,
    ): Response {
        $this->authorize('view', $pegawai);

        $filter = $request->validated();
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $bulan = $request->query->has('bulan')
            ? (isset($filter['bulan']) ? (int) $filter['bulan'] : null)
            : $sekarang->month;
        $labelPeriode = $bulan === null
            ? (string) $tahun
            : CarbonImmutable::create($tahun, $bulan, 1)
                ->locale('id')
                ->translatedFormat('F Y');
        $kodePeriode = $bulan === null ? (string) $tahun : sprintf('%04d-%02d', $tahun, $bulan);
        $data = $ambilDetail($pegawai, $tahun, $bulan);
        $pdf = $cetak($data, $pegawai, $labelPeriode);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="slip-tukin-'.Str::slug($pegawai->nama)."-{$kodePeriode}.pdf\"",
        ]);
    }

    /**
     * @return array{0: User, 1: Pegawai|null, 2: int, 3: int|null, 4: string, 5: string}
     */
    private function siapkan(TukinRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAny', Pegawai::class);

        $filter = $request->validated();
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $bulan = $request->query->has('bulan')
            ? (isset($filter['bulan']) ? (int) $filter['bulan'] : null)
            : $sekarang->month;
        $idPegawai = isset($filter['id_pegawai']) ? (int) $filter['id_pegawai'] : null;
        $pegawai = $idPegawai === null ? null : Pegawai::query()->find($idPegawai);

        if ($pegawai !== null) {
            $this->authorize('view', $pegawai);
        }

        $labelPeriode = $bulan === null
            ? (string) $tahun
            : CarbonImmutable::create($tahun, $bulan, 1)
                ->locale('id')
                ->translatedFormat('F Y');
        $kodePeriode = $bulan === null
            ? (string) $tahun
            : sprintf('%04d-%02d', $tahun, $bulan);

        return [$user, $pegawai, $tahun, $bulan, $labelPeriode, $kodePeriode];
    }
}
