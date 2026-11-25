<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class PengajuanDetailController extends Controller
{
    /**
     * Detail satu pengajuan milik pegawai. Policy `view` menolak pengajuan
     * milik pegawai lain dengan 403.
     */
    public function __invoke(Pengajuan $pengajuan): View
    {
        $this->authorize('view', $pengajuan);

        $pengajuan->loadMissing(['cuti', 'penyetuju']);

        $adaLampiran = $pengajuan->lampiran !== null
            && Storage::disk('local')->exists($pengajuan->lampiran);

        return view('pegawai.pengajuan-show', [
            'pengajuan' => $pengajuan,
            'adaLampiran' => $adaLampiran,
        ]);
    }
}
