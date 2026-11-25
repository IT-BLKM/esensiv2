<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\CetakPdfLaporanAction;
use App\Actions\Laporan\LaporanBulananAction;
use App\Actions\Laporan\LaporanSemuaPegawaiAction;
use App\Actions\Laporan\TulisExcelLaporanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanBulananRequest;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanExportController extends Controller
{
    /**
     * Unduh Excel per pegawai (bila id_pegawai diisi) atau matriks semua
     * pegawai untuk bulan/tahun terpilih.
     */
    public function excel(
        LaporanBulananRequest $request,
        LaporanBulananAction $laporan,
        LaporanSemuaPegawaiAction $semua,
        TulisExcelLaporanAction $tulis,
    ): BinaryFileResponse {
        [$user, $pegawai, $bulan, $tahun] = $this->siapkan($request);
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        if ($pegawai === null) {
            $data = $semua($user, $bulan, $tahun);
            $path = $tulis->semua($data);
            $namaFile = "rekap-presensi-semua-{$periode}.xlsx";
        } else {
            $data = $laporan($user, $pegawai, $bulan, $tahun);
            $path = $tulis->pegawai($data, $pegawai);
            $namaFile = 'laporan-presensi-'.Str::slug($pegawai->nama)."-{$periode}.xlsx";
        }

        return response()->download($path, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Unduh PDF per pegawai (dengan foto) atau matriks semua pegawai.
     */
    public function pdf(
        LaporanBulananRequest $request,
        LaporanBulananAction $laporan,
        LaporanSemuaPegawaiAction $semua,
        CetakPdfLaporanAction $cetak,
    ): Response {
        [$user, $pegawai, $bulan, $tahun] = $this->siapkan($request);
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        if ($pegawai === null) {
            $data = $semua($user, $bulan, $tahun);
            $pdf = $cetak->semua($data);
            $namaFile = "rekap-presensi-semua-{$periode}.pdf";
        } else {
            $data = $laporan($user, $pegawai, $bulan, $tahun);
            $pdf = $cetak->pegawai($data, $pegawai, $this->fotoUntuk($data['baris']));
            $namaFile = 'laporan-presensi-'.Str::slug($pegawai->nama)."-{$periode}.pdf";
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
        ]);
    }

    /**
     * @return array{0: User, 1: Pegawai|null, 2: int, 3: int}
     */
    private function siapkan(LaporanBulananRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $filter = $request->validated();
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $bulan = (int) ($filter['bulan'] ?? $sekarang->month);
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $idPegawai = isset($filter['id_pegawai']) ? (int) $filter['id_pegawai'] : null;
        $pegawai = $idPegawai === null ? null : Pegawai::query()->find($idPegawai);

        if ($pegawai === null) {
            $this->authorize('viewAny', Pegawai::class);
        } else {
            $this->authorize('view', $pegawai);
        }

        return [$user, $pegawai, $bulan, $tahun];
    }

    /**
     * @param  list<array<string, mixed>>  $baris
     * @return array<int, array{masuk: string|null, keluar: string|null}>
     */
    private function fotoUntuk(array $baris): array
    {
        $ids = array_column($baris, 'id');

        return Presensi::query()
            ->whereIn('id', $ids)
            ->get(['id', 'foto_masuk', 'foto_keluar'])
            ->mapWithKeys(fn (Presensi $presensi): array => [
                $presensi->id => [
                    'masuk' => $this->dataUri($presensi->foto_masuk),
                    'keluar' => $this->dataUri($presensi->foto_keluar),
                ],
            ])
            ->all();
    }

    private function dataUri(?string $path): ?string
    {
        if ($path === null || $path === '' || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) Storage::disk('local')->get($path));
    }
}
