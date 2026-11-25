<x-layouts.pegawai title="Presensi">
    @if ($hariIni === null)
        <div class="flex flex-col gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Presensi</h1>
                <p class="mt-1 text-sm text-gray-600">Absen masuk dan pulang dengan foto, lokasi GPS, dan verifikasi wajah.</p>
            </div>

            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai, jadi presensi belum bisa dilakukan.
                Hubungi administrator untuk melengkapi data.
            </x-alert>
        </div>
    @else
        @php
            $shift = $hariIni['shift'];
            $status = $hariIni['status'];
            $jamMasuk = $hariIni['jamMasuk'];
            $jamKeluar = $hariIni['jamKeluar'];
            $dlSetengah = $pengajuanDl !== null
                && $pengajuanDl->jenis === 'dl'
                && $pengajuanDl->mode_dl === 'setengah';

            if ($status !== null && $status !== 'hadir') {
                $mode = 'libur';
                $labelStatus = $hariIni['statusLabel'];
                $variantStatus = match ($status) {
                    'cuti' => 'info',
                    'sakit' => 'warning',
                    'dl' => 'brand',
                    default => 'neutral',
                };
            } elseif ($jamKeluar !== null) {
                $mode = 'selesai';
                $labelStatus = 'Presensi selesai';
                $variantStatus = 'success';
            } elseif ($shift === null) {
                $mode = 'tanpa-shift';
                $labelStatus = 'Tidak ada shift';
                $variantStatus = 'neutral';
            } elseif ($status === 'hadir' && $jamMasuk === null) {
                $mode = 'data-tidak-lengkap';
                $labelStatus = 'Data tidak lengkap';
                $variantStatus = 'danger';
            } elseif ($jamMasuk !== null) {
                $mode = 'keluar';
                $labelStatus = 'Sudah masuk';
                $variantStatus = 'success';
            } else {
                $mode = 'masuk';
                $labelStatus = 'Belum absen';
                $variantStatus = 'neutral';
            }

            if ($dlSetengah) {
                if ($status === 'hadir' && $jamMasuk !== null) {
                    $mode = 'dl-setengah-selesai';
                    $labelStatus = 'DL setengah selesai';
                    $variantStatus = 'success';
                } elseif ($shift !== null && $status === null) {
                    $mode = 'dl-setengah';
                    $labelStatus = 'DL setengah hari';
                    $variantStatus = 'info';
                }
            }

            $bisaAbsen = in_array($mode, ['masuk', 'keluar', 'dl-setengah'], true);
            $labelTombol = match ($mode) {
                'masuk' => 'Absen Masuk',
                'keluar' => 'Absen Pulang',
                'dl-setengah' => 'Absen Sekali',
                default => 'Absen tidak tersedia',
            };

            $alasanTombol = match ($mode) {
                'masuk', 'keluar' => 'Nyalakan kamera, ambil lokasi, lalu hadapkan wajah untuk mengaktifkan tombol.',
                'dl-setengah' => 'DL setengah hari: cukup absen sekali hari ini, tanpa absen pulang.',
                'dl-setengah-selesai' => 'Absen DL setengah hari sudah tercatat; absen pulang tidak diperlukan.',
                'tanpa-shift' => 'Tidak ada shift terjadwal hari ini.',
                'libur' => 'Hari ini tercatat '.$labelStatus.'.',
                'selesai' => 'Presensi hari ini sudah lengkap.',
                default => 'Data presensi hari ini tidak lengkap.',
            };
        @endphp

        <div
            id="halaman-presensi"
            class="flex flex-col gap-4"
            data-mode="{{ $mode }}"
            data-bisa-absen="{{ $bisaAbsen ? '1' : '0' }}"
            data-kantor-latitude="{{ $kantor['latitude'] }}"
            data-kantor-longitude="{{ $kantor['longitude'] }}"
            data-kantor-radius="{{ $kantor['radius'] }}"
            data-models-url="{{ asset('models') }}"
            data-store-url="{{ route('presensi.store') }}"
        >
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Presensi</h1>
                <p class="mt-1 text-sm text-gray-600">Absen masuk dan pulang dengan foto, lokasi GPS, dan verifikasi wajah.</p>
            </div>

            <x-card title="Status hari ini" :subtitle="$hariIni['labelTanggal']">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :variant="$variantStatus">{{ $labelStatus }}</x-badge>

                        @if ($hariIni['terlambat'])
                            <x-badge variant="warning">Terlambat {{ $hariIni['menitTerlambat'] }} menit</x-badge>
                        @endif
                    </div>

                    @if ($shift !== null)
                        <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between gap-4 pb-2">
                                <dt class="text-gray-600">Shift</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $shift['nama'] }}
                                    <span class="font-normal text-gray-600">({{ $shift['kode'] }})</span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <dt class="text-gray-600">Jadwal</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $shift['jamMasuk'] }} &ndash; {{ $shift['jamPulang'] }}
                                </dd>
                            </div>
                        </dl>
                    @endif

                    @if ($jamMasuk !== null || $jamKeluar !== null)
                        <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between gap-4 pb-2">
                                <dt class="text-gray-600">Jam masuk</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $jamMasuk ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <dt class="text-gray-600">Jam keluar</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $jamKeluar ?? '—' }}</dd>
                            </div>
                        </dl>
                    @endif

                    @if ($mode === 'dl-setengah')
                        <x-alert variant="info">
                            Hari ini Anda tercatat <strong>Dinas Luar setengah hari</strong>.
                            Cukup absen sekali kapan saja sampai jam pulang; absen pulang tidak diperlukan
                            dan keterlambatan tidak dihitung.
                        </x-alert>
                    @elseif ($mode === 'dl-setengah-selesai')
                        <x-alert variant="success">
                            Absen Dinas Luar setengah hari sudah tercatat. Tidak perlu absen pulang.
                        </x-alert>
                    @elseif ($mode === 'tanpa-shift')
                        <x-alert variant="info">
                            Tidak ada shift terjadwal hari ini, jadi tombol absen dinonaktifkan.
                            Hubungi administrator bila jadwal ini keliru.
                        </x-alert>
                    @elseif ($mode === 'libur')
                        <x-alert variant="info">
                            Hari ini Anda tercatat <strong>{{ $labelStatus }}</strong> yang sudah disetujui,
                            jadi tidak perlu melakukan absen.
                        </x-alert>
                    @elseif ($mode === 'selesai')
                        <x-alert variant="success">
                            Presensi hari ini sudah lengkap. Terima kasih.
                        </x-alert>
                    @elseif ($mode === 'data-tidak-lengkap')
                        <x-alert variant="danger">
                            Baris presensi hari ini ada, tetapi jam masuknya tidak tercatat.
                            Hubungi administrator sebelum mencoba absen lagi.
                        </x-alert>
                    @endif
                </div>
            </x-card>

            <x-card title="Kamera" subtitle="Foto diambil dari kamera ini saat Anda menekan tombol absen.">
                <div class="flex flex-col gap-3">
                    <div class="relative aspect-video overflow-hidden rounded-md bg-gray-900">
                        <video
                            id="video-presensi"
                            class="h-full w-full -scale-x-100 object-cover"
                            autoplay
                            playsinline
                            muted
                        ></video>

                        <div
                            id="placeholder-kamera"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-6 text-center text-sm text-gray-200"
                        >
                            <x-icon name="presensi" size="lg" />
                            <p>Kamera belum aktif.</p>
                        </div>

                        <div class="pointer-events-none absolute inset-0 flex items-end justify-center pb-3">
                            <span class="rounded-full bg-gray-900/70 px-3 py-1 text-xs text-white">Posisikan wajah di tengah bingkai</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-button type="button" id="tombol-kamera" variant="secondary" size="sm">Nyalakan kamera</x-button>
                        <p id="status-wajah" class="text-sm text-gray-600" role="status" aria-live="polite">Kamera belum aktif.</p>
                    </div>

                    <x-alert id="pesan-kamera" variant="danger" class="hidden" role="alert"></x-alert>
                    <x-alert id="pesan-model" variant="warning" class="hidden" role="status">
                        Deteksi wajah tidak tersedia. Webcam tetap bisa dipakai; absen dapat
                        dilanjutkan tanpa verifikasi wajah.
                    </x-alert>
                </div>
            </x-card>

            <x-card title="Lokasi Anda" subtitle="Indikator UI — server tetap memvalidasi radius pada issue #32.">
                <div class="flex flex-col gap-3">
                    <div
                        id="peta-presensi"
                        class="h-56 w-full rounded-md border border-gray-200 bg-gray-100"
                        role="img"
                        aria-label="Peta lokasi kantor dan posisi Anda"
                    ></div>

                    <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-start sm:gap-0">
                            <dt class="text-gray-600">Koordinat</dt>
                            <dd id="teks-koordinat" class="text-right font-medium text-gray-900 sm:text-left">Belum diambil</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-start sm:gap-0">
                            <dt class="text-gray-600">Jarak ke kantor</dt>
                            <dd id="teks-jarak" class="text-right font-medium text-gray-900 sm:text-left">—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-start sm:gap-0">
                            <dt class="text-gray-600">Akurasi</dt>
                            <dd id="teks-akurasi" class="text-right font-medium text-gray-900 sm:text-left">—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-start sm:gap-0">
                            <dt class="text-gray-600">Status radius</dt>
                            <dd id="teks-radius" class="text-right font-medium text-gray-900 sm:text-left">—</dd>
                        </div>
                    </dl>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-button type="button" id="tombol-lokasi" variant="secondary" size="sm">Ambil lokasi</x-button>
                        <p class="text-xs text-gray-500">Peta memuat ubin OpenStreetMap dan memerlukan internet; indikator teks tetap bekerja tanpa peta.</p>
                    </div>

                    <x-alert id="pesan-lokasi" variant="danger" class="hidden" role="alert"></x-alert>
                </div>
            </x-card>

            <x-card title="Kirim presensi" subtitle="Foto dan lokasi divalidasi ulang di server sebelum disimpan.">
                <form id="form-presensi" method="POST" action="{{ route('presensi.store') }}" class="flex flex-col gap-3">
                    @csrf

                    <input type="hidden" name="jenis" id="input-jenis" value="{{ $mode === 'keluar' ? 'keluar' : 'masuk' }}">
                    <input type="hidden" name="foto" id="input-foto">
                    <input type="hidden" name="koordinat" id="input-koordinat">
                    <input type="hidden" name="akurasi" id="input-akurasi">

                    <div class="flex flex-wrap items-center gap-2">
                        <x-button
                            type="submit"
                            id="tombol-absen"
                            variant="primary"
                            disabled
                            aria-describedby="alasan-tombol"
                        >{{ $labelTombol }}</x-button>

                        <p id="alasan-tombol" class="text-xs text-gray-600">{{ $alasanTombol }}</p>
                    </div>

                    <x-alert id="pesan-presensi" variant="info" class="hidden" role="status" aria-live="polite"></x-alert>
                </form>
            </x-card>
        </div>

        @push('scripts')
            @vite('resources/js/presensi.js')
        @endpush
    @endif
</x-layouts.pegawai>
