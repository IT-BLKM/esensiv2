<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Pengajuan\CariPengajuanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPengajuanRequest;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class PengajuanDaftarController extends Controller
{
    /**
     * Daftar pengajuan milik pegawai yang login (issue #38), dengan filter
     * status/bulan/tahun dan tautan detail.
     */
    public function __invoke(DaftarPengajuanRequest $request, CariPengajuanAction $cari): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        $filter = $request->validated();
        $status = $filter['status'] ?? null;
        $bulan = isset($filter['bulan']) ? (int) $filter['bulan'] : null;
        $tahun = isset($filter['tahun']) ? (int) $filter['tahun'] : null;

        return view('pegawai.pengajuan', [
            'pengajuan' => $pegawai === null ? collect() : $cari($user, $status, $bulan, $tahun),
            'status' => $status,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pilihanBulan' => $this->pilihanBulan(),
            'pilihanTahun' => $this->pilihanTahun($pegawai, $tahun),
            'adaPegawai' => $pegawai !== null,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function pilihanBulan(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $bulan): array => [
                $bulan => CarbonImmutable::create(2000, $bulan, 1)
                    ->locale('id')
                    ->translatedFormat('F'),
            ])
            ->all();
    }

    /**
     * Tahun dari pengajuan paling awal sampai tahun berjalan, ditambah tahun
     * yang sedang difilter agar pilihan tetap tampil walau tidak ada data.
     *
     * @return list<int>
     */
    private function pilihanTahun(?Pegawai $pegawai, ?int $tahunDipilih): array
    {
        $tahunSekarang = CarbonImmutable::now(
            (string) config('presensi.zona_waktu', 'Asia/Jakarta')
        )->year;

        $tahunAwal = $tahunSekarang;

        if ($pegawai !== null) {
            $terawal = Pengajuan::query()
                ->where('id_pegawai', $pegawai->id)
                ->min('tanggal_mulai');

            if (is_string($terawal) && $terawal !== '') {
                $tahunAwal = min($tahunAwal, (int) Carbon::parse($terawal)->year);
            }
        }

        $pilihan = range($tahunSekarang, $tahunAwal);

        if ($tahunDipilih !== null && ! in_array($tahunDipilih, $pilihan, true)) {
            $pilihan[] = $tahunDipilih;
            rsort($pilihan);
        }

        return array_values($pilihan);
    }
}
