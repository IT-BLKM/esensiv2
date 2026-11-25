<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanLampiranController extends Controller
{
    /**
     * Unduh lampiran dari disk privat. Policy `view` memastikan hanya pemilik
     * (atau admin) yang bisa mengaksesnya.
     */
    public function __invoke(Pengajuan $pengajuan): StreamedResponse
    {
        $this->authorize('view', $pengajuan);

        abort_if($pengajuan->lampiran === null, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($pengajuan->lampiran), 404);

        $ekstensi = pathinfo($pengajuan->lampiran, PATHINFO_EXTENSION);
        $nama = 'lampiran-pengajuan-'.$pengajuan->id.($ekstensi === '' ? '' : '.'.$ekstensi);

        return $disk->download($pengajuan->lampiran, $nama);
    }
}
