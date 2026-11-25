<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresensiFotoController extends Controller
{
    /**
     * Sajikan foto presensi dari disk privat. Policy `view` membatasi akses
     * ke pemilik presensi atau admin.
     */
    public function __invoke(Presensi $presensi, string $jenis): StreamedResponse
    {
        $this->authorize('view', $presensi);

        abort_unless(in_array($jenis, ['masuk', 'keluar'], true), 404);

        $path = $jenis === 'masuk' ? $presensi->foto_masuk : $presensi->foto_keluar;

        abort_if($path === null || $path === '', 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $ekstensi = pathinfo($path, PATHINFO_EXTENSION);
        $mime = in_array(strtolower($ekstensi), ['png'], true) ? 'image/png' : 'image/jpeg';
        $nama = 'presensi-'.$presensi->id.'-'.$jenis.($ekstensi === '' ? '' : '.'.$ekstensi);

        return $disk->response($path, $nama, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
