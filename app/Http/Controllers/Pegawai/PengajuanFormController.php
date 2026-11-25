<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Cuti\HitungSisaCutiAction;
use App\Actions\Pengajuan\CreatePengajuanAction;
use App\Actions\Pengajuan\DeletePengajuanAction;
use App\Actions\Pengajuan\UpdatePengajuanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengajuanRequest;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman form pengajuan pegawai (issue #36, edit pending #103).
 *
 * Endpoint JSON pegawai.pengajuan.* tetap dipakai oleh histori (#33), jadi
 * controller ini hanya menangani halaman HTML dan pengiriman form.
 */
class PengajuanFormController extends Controller
{
    public function __construct(private readonly HitungSisaCutiAction $hitungSisaCuti) {}

    public function create(Request $request): View
    {
        return $this->form($request);
    }

    public function store(StorePengajuanRequest $request, CreatePengajuanAction $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $pending = $this->pendingTahunanUntuk(
            $user->pegawai,
            $request->input('id_cuti'),
        );

        if ($pending !== null) {
            return redirect()->route('pengajuan.edit', $pending)
                ->with('warning', 'Masih ada pengajuan Cuti Tahunan yang menunggu. Silakan ubah pengajuan tersebut agar tidak dobel.');
        }

        $create($user, $request->validated());

        return redirect()->route('pengajuan.index')
            ->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan.');
    }

    public function edit(Request $request, Pengajuan $pengajuan): View
    {
        $this->authorize('update', $pengajuan);

        abort_unless($pengajuan->status === 'pending', 403);

        return $this->form($request, $pengajuan);
    }

    public function cancel(
        Request $request,
        Pengajuan $pengajuan,
        DeletePengajuanAction $delete,
    ): RedirectResponse {
        $this->authorize('delete', $pengajuan);

        /** @var User $user */
        $user = $request->user();
        $delete($user, $pengajuan);

        return redirect()->route('pengajuan.index')
            ->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function update(
        StorePengajuanRequest $request,
        Pengajuan $pengajuan,
        UpdatePengajuanAction $update,
    ): RedirectResponse {
        $this->authorize('update', $pengajuan);

        abort_unless($pengajuan->status === 'pending', 403);

        /** @var User $user */
        $user = $request->user();

        $update($user, $pengajuan, $request->validated());

        return redirect()->route('pengajuan.show', $pengajuan)
            ->with('success', 'Pengajuan berhasil diperbarui dan tetap menunggu persetujuan.');
    }

    private function form(Request $request, ?Pengajuan $pengajuan = null): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        $cuti = Cuti::query()->orderBy('kode')->get(['id', 'kode', 'nama', 'kuota', 'maks_hari', 'maks_bawa']);

        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $oldMulai = $request->old('tanggal_mulai', $pengajuan?->tanggal_mulai);

        if (is_string($oldMulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $oldMulai) === 1) {
            $tahun = (int) substr($oldMulai, 0, 4);
        }

        $sisa = [];

        if ($pegawai !== null) {
            foreach ($cuti as $item) {
                $sisa[$item->id] = ($this->hitungSisaCuti)($pegawai, $item, $tahun);
            }
        }

        return view($pengajuan === null ? 'pegawai.pengajuan-create' : 'pegawai.pengajuan-edit', [
            'cuti' => $cuti,
            'sisa' => $sisa,
            'tahunTampil' => $tahun,
            'adaPegawai' => $pegawai !== null,
            'pengajuan' => $pengajuan,
            'pendingTahunan' => $pengajuan === null
                ? $this->pendingTahunanUntuk($pegawai)
                : null,
        ]);
    }

    /**
     * Cari pengajuan Cuti Tahunan (C01) yang masih pending milik pegawai.
     * Dipakai untuk mencegah pengajuan C01 ganda dan menautkan halaman edit.
     */
    private function pendingTahunanUntuk(?Pegawai $pegawai, mixed $idCutiDiminta = null): ?Pengajuan
    {
        if ($pegawai === null) {
            return null;
        }

        $idCutiTahunan = Cuti::query()
            ->where('kode', Cuti::KODE_TAHUNAN)
            ->value('id');

        if ($idCutiTahunan === null) {
            return null;
        }

        if ($idCutiDiminta !== null && (int) $idCutiDiminta !== (int) $idCutiTahunan) {
            return null;
        }

        return Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('status', 'pending')
            ->where('jenis', 'cuti')
            ->where('id_cuti', $idCutiTahunan)
            ->orderByDesc('id')
            ->first();
    }
}
