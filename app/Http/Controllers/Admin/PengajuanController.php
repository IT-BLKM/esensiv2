<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pengajuan\ApprovePengajuanAction;
use App\Actions\Pengajuan\CancelApprovalPengajuanAction;
use App\Actions\Pengajuan\CariPengajuanAdminAction;
use App\Actions\Pengajuan\DaftarHariKerjaAction;
use App\Actions\Pengajuan\RejectPengajuanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPengajuanAdminRequest;
use App\Http\Requests\SetujuiPengajuanRequest;
use App\Http\Requests\TolakPengajuanRequest;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PengajuanController extends Controller
{
    /**
     * Panel admin pengajuan (issue #39): filter, detail, approve/reject.
     */
    public function index(DaftarPengajuanAdminRequest $request, CariPengajuanAdminAction $cari): View
    {
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('viewAny', Pengajuan::class);

        $filter = $request->validated();

        // Default actionable view = pending. Kirim ?status= (kosong) untuk semua.
        $status = $request->query->has('status') ? ($filter['status'] ?? null) : 'pending';
        $idPegawai = isset($filter['id_pegawai']) ? (int) $filter['id_pegawai'] : null;
        $tanggalDari = $filter['tanggal_dari'] ?? null;
        $tanggalSampai = $filter['tanggal_sampai'] ?? null;

        return view('admin.pengajuan', [
            'pengajuan' => $cari($user, $status, $idPegawai, $tanggalDari, $tanggalSampai),
            'status' => $status,
            'idPegawai' => $idPegawai,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'daftarPegawai' => Pegawai::query()->orderBy('nama')->get(['id', 'nip', 'nama']),
        ]);
    }

    public function show(Pengajuan $pengajuan, DaftarHariKerjaAction $daftarHariKerja): View
    {
        $this->authorize('view', $pengajuan);

        $pengajuan->loadMissing(['pegawai', 'cuti', 'penyetuju']);

        $mulai = CarbonImmutable::parse((string) $pengajuan->tanggal_mulai)->startOfDay();
        $selesai = CarbonImmutable::parse((string) $pengajuan->tanggal_berakhir)->startOfDay();

        $hariKerja = $pengajuan->pegawai === null
            ? []
            : ($daftarHariKerja)($pengajuan->pegawai, $mulai, $selesai);

        return view('admin.pengajuan-show', [
            'pengajuan' => $pengajuan,
            'hariKerja' => $hariKerja,
            'jumlahPresensiTerkait' => Presensi::query()
                ->where('id_pengajuan', $pengajuan->id)
                ->count(),
            'adaLampiran' => $pengajuan->lampiran !== null
                && Storage::disk('local')->exists($pengajuan->lampiran),
        ]);
    }

    public function approve(
        SetujuiPengajuanRequest $request,
        Pengajuan $pengajuan,
        ApprovePengajuanAction $approve,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $hasil = $approve($user, $pengajuan, $request->validated('catatan_setujui'));

        $pesan = $hasil['pengajuan']->jenis === 'dl'
            && $hasil['pengajuan']->mode_dl === Pengajuan::MODE_DL_SETENGAH
                ? 'Pengajuan Dinas Luar setengah hari disetujui. Pegawai cukup absen sekali pada hari tersebut.'
                : "Pengajuan disetujui. {$hasil['jumlahPresensi']} baris presensi hari kerja dibuat.";

        return redirect()->route('admin.pengajuan.show', $pengajuan)
            ->with('success', $pesan);
    }

    public function reject(
        TolakPengajuanRequest $request,
        Pengajuan $pengajuan,
        RejectPengajuanAction $reject,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $reject($user, $pengajuan, $request->validated('catatan_tolak'));

        return redirect()->route('admin.pengajuan.show', $pengajuan)
            ->with('success', 'Pengajuan ditolak.');
    }

    public function cancel(
        Request $request,
        Pengajuan $pengajuan,
        CancelApprovalPengajuanAction $cancel,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $hasil = $cancel($user, $pengajuan);

        return redirect()->route('admin.pengajuan.show', $pengajuan)
            ->with('success', "Persetujuan dibatalkan. {$hasil['jumlahPresensiDihapus']} baris presensi dihapus.");
    }
}
