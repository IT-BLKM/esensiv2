<?php

namespace App\Actions\Presensi;

use App\Actions\Pengajuan\CariPengajuanDlHariIniAction;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StorePresensiAction
{
    public function __construct(
        private readonly CariPengajuanDlHariIniAction $cariDlHariIni,
        private readonly HitungPenggantianKeterlambatanAction $hitungPenggantian,
    ) {}

    /**
     * Batas ukuran foto setelah base64 didekode (byte).
     */
    private const MAKS_BYTE_FOTO = 3 * 1024 * 1024;

    /**
     * Format gambar yang diterima dari canvas halaman presensi.
     *
     * @var array<int, string>
     */
    private const MIME_FOTO = ['image/jpeg', 'image/png'];

    /**
     * Catat absen masuk atau pulang untuk satu pegawai pada hari ini.
     *
     * Server tidak mempercayai klien: shift, radius (Haversine), jenis alur,
     * dan foto divalidasi ulang di sini. Foto disimpan di disk `local`
     * (storage/app/private), bukan storage publik.
     *
     * @param  array{jenis: string, foto: string, koordinat: string, akurasi?: int|string|null, konfirmasi_pulang_cepat?: bool|string|null}  $data
     * @return array{jenis: string, presensi: Presensi}
     *
     * @throws PresensiException
     */
    public function __invoke(Pegawai $pegawai, array $data): array
    {
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $jenis = $data['jenis'];
        $konfirmasiPulangCepat = (bool) ($data['konfirmasi_pulang_cepat'] ?? false);
        $koordinat = $this->normalisasiKoordinat($data['koordinat']);
        $shift = $this->shiftHariIni($pegawai, $sekarang);

        $dlHariIni = ($this->cariDlHariIni)($pegawai, $sekarang);
        $dlSetengah = $dlHariIni?->mode_dl === Pengajuan::MODE_DL_SETENGAH;

        $this->pastikanDalamRadius($koordinat);
        [$isiFoto, $ekstensiFoto] = $this->bacaFoto($data['foto']);

        $pathFoto = null;

        try {
            $presensi = DB::transaction(function () use (
                $pegawai,
                $jenis,
                $konfirmasiPulangCepat,
                $koordinat,
                $isiFoto,
                $ekstensiFoto,
                $shift,
                $sekarang,
                $dlHariIni,
                $dlSetengah,
                &$pathFoto,
            ): Presensi {
                $presensi = Presensi::query()
                    ->where('id_pegawai', $pegawai->id)
                    ->where('tgl_presensi', $sekarang->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($presensi !== null && $presensi->status !== 'hadir') {
                    throw PresensiException::statusHariIni($presensi->status);
                }

                if ($jenis === 'masuk') {
                    if ($presensi !== null) {
                        throw PresensiException::sudahMasuk();
                    }

                    $jamSekarang = $sekarang->format('H:i:s');

                    if ($dlSetengah) {
                        if ($jamSekarang > $shift->jam_pulang) {
                            throw PresensiException::dlSetengahLewat($shift->jam_pulang);
                        }
                    } else {
                        if ($jamSekarang < $shift->awal_masuk) {
                            throw PresensiException::sebelumAwalMasuk($shift->awal_masuk);
                        }

                        if ($jamSekarang > $shift->akhir_masuk) {
                            throw PresensiException::setelahAkhirMasuk($shift->akhir_masuk);
                        }
                    }

                    $pathFoto = $this->simpanFoto($pegawai, 'masuk', $sekarang, $isiFoto, $ekstensiFoto);

                    return Presensi::create([
                        'id_pegawai' => $pegawai->id,
                        'id_jam_kerja' => $shift->id,
                        'id_pengajuan' => $dlSetengah ? $dlHariIni?->id : null,
                        'tgl_presensi' => $sekarang->toDateString(),
                        'jam_masuk' => $sekarang->format('H:i:s'),
                        'jam_keluar' => null,
                        'foto_masuk' => $pathFoto,
                        'foto_keluar' => null,
                        'koordinat_masuk' => $koordinat['teks'],
                        'koordinat_keluar' => null,
                        'status' => 'hadir',
                    ]);
                }

                if ($dlSetengah) {
                    throw PresensiException::dlSetengahSatuKali();
                }

                if ($presensi === null || $presensi->jam_masuk === null) {
                    throw PresensiException::belumMasuk();
                }

                if ($presensi->jam_keluar !== null) {
                    throw PresensiException::sudahKeluar();
                }

                /*
                 * Pulang lebih awal boleh dicatat setelah pegawai menyetujui
                 * konfirmasi di halaman presensi. Flag ini mencegah checkout
                 * tidak sengaja lewat API tanpa konfirmasi.
                 */
                if ($sekarang->format('H:i:s') < $shift->jam_pulang && ! $konfirmasiPulangCepat) {
                    throw PresensiException::sebelumJamPulang($shift->jam_pulang);
                }

                $pathFoto = $this->simpanFoto($pegawai, 'keluar', $sekarang, $isiFoto, $ekstensiFoto);

                $presensi->update([
                    'jam_keluar' => $sekarang->format('H:i:s'),
                    'foto_keluar' => $pathFoto,
                    'koordinat_keluar' => $koordinat['teks'],
                ]);

                $presensi->refresh();

                $this->simpanPenggantianKeterlambatan($presensi, $shift);

                return $presensi;
            });
        } catch (Throwable $error) {
            if (is_string($pathFoto)) {
                Storage::disk('local')->delete($pathFoto);
            }

            /*
             * Dobel klik race: dua request masuk lolos pengecekan awal, lalu
             * yang kalah menabrak UNIQUE(tgl_presensi, id_pegawai). Ubah jadi
             * respons 409 yang jelas, bukan error 500.
             */
            if (
                $error instanceof QueryException
                && $jenis === 'masuk'
                && $this->sudahAdaPresensi($pegawai, $sekarang)
            ) {
                throw PresensiException::sudahMasuk();
            }

            throw $error;
        }

        return [
            'jenis' => $jenis,
            'presensi' => $presensi,
        ];
    }

    /**
     * Simpan snapshot penggantian otomatis setelah absen pulang. Baris lama
     * tanpa snapshot tetap dihitung langsung dari jam masuk/keluar.
     */
    private function simpanPenggantianKeterlambatan(Presensi $presensi, JamKerja $shift): void
    {
        $hasil = ($this->hitungPenggantian)($presensi, $shift);

        if ($hasil === null || $hasil['jamSelesai'] === null) {
            return;
        }

        PenggantianKeterlambatan::query()->updateOrCreate(
            ['id_presensi' => $presensi->id],
            [
                'tanggal' => $hasil['tanggal'],
                'jam_mulai' => $hasil['jamMulai'],
                'jam_selesai' => $hasil['jamSelesai'],
                'target_pulang' => $hasil['targetPulang'],
                'batas_pulang' => $hasil['batasPulang'],
                'menit_terlambat' => $hasil['menitTerlambat'],
                'menit_diganti' => $hasil['menitDiganti'],
                'bisa_diganti' => $hasil['bisaDiganti'],
                'terpenuhi' => $hasil['terpenuhi'],
                'catatan' => $hasil['catatan'],
            ],
        );
    }

    private function shiftHariIni(Pegawai $pegawai, CarbonInterface $sekarang): JamKerja
    {
        $namaHari = KonfigurasiJamKerja::HARI[$sekarang->dayOfWeekIso - 1];

        $shift = KonfigurasiJamKerja::query()
            ->with('jamKerja')
            ->where('id_pegawai', $pegawai->id)
            ->where('hari', $namaHari)
            ->first()
            ?->jamKerja;

        if ($shift === null) {
            throw PresensiException::jadwal();
        }

        return $shift;
    }

    /**
     * @return array{latitude: float, longitude: float, teks: string}
     */
    private function normalisasiKoordinat(string $koordinat): array
    {
        [$lintang, $bujur] = array_map('trim', explode(',', $koordinat, 2));

        return [
            'latitude' => (float) $lintang,
            'longitude' => (float) $bujur,
            'teks' => $lintang.','.$bujur,
        ];
    }

    /**
     * @param  array{latitude: float, longitude: float, teks: string}  $koordinat
     */
    private function pastikanDalamRadius(array $koordinat): void
    {
        $radius = (int) config('presensi.radius');
        $jarak = $this->jarakMeter(
            $koordinat['latitude'],
            $koordinat['longitude'],
            (float) config('presensi.latitude'),
            (float) config('presensi.longitude'),
        );

        if ($jarak > $radius) {
            throw PresensiException::radius($jarak, $radius);
        }
    }

    /**
     * Jarak Haversine dalam meter — bukan selisih derajat mentah.
     */
    private function jarakMeter(float $lintang1, float $bujur1, float $lintang2, float $bujur2): float
    {
        $radiusBumi = 6371000.0;
        $selisihLintang = deg2rad($lintang2 - $lintang1);
        $selisihBujur = deg2rad($bujur2 - $bujur1);

        $a = sin($selisihLintang / 2) ** 2
            + cos(deg2rad($lintang1)) * cos(deg2rad($lintang2)) * sin($selisihBujur / 2) ** 2;

        return 2 * $radiusBumi * asin(sqrt($a));
    }

    /**
     * @return array{0: string, 1: string} isi biner dan ekstensi file
     */
    private function bacaFoto(string $foto): array
    {
        if (preg_match('#^data:image/(jpeg|jpg|png);base64,(.+)$#is', $foto, $cocok) !== 1) {
            throw PresensiException::foto();
        }

        $isi = base64_decode($cocok[2], true);

        if ($isi === false || $isi === '' || strlen($isi) > self::MAKS_BYTE_FOTO) {
            throw PresensiException::foto();
        }

        $info = @getimagesizefromstring($isi);

        if ($info === false || ! in_array($info['mime'], self::MIME_FOTO, true)) {
            throw PresensiException::foto();
        }

        return [$isi, $info['mime'] === 'image/png' ? 'png' : 'jpg'];
    }

    private function simpanFoto(
        Pegawai $pegawai,
        string $jenis,
        CarbonInterface $sekarang,
        string $isi,
        string $ekstensi,
    ): string {
        $path = sprintf(
            'presensi/%s/%d-%s-%s.%s',
            $sekarang->format('Y/m/d'),
            $pegawai->id,
            $jenis,
            Str::uuid()->toString(),
            $ekstensi,
        );

        if (! Storage::disk('local')->put($path, $isi)) {
            throw PresensiException::fotoGagalDisimpan();
        }

        return $path;
    }

    private function sudahAdaPresensi(Pegawai $pegawai, CarbonInterface $sekarang): bool
    {
        return Presensi::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('tgl_presensi', $sekarang->toDateString())
            ->exists();
    }
}
