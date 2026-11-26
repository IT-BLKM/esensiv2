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
            $periodeTerkunci = $periodeTerkunci ?? false;

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
            } elseif ($periodeTerkunci && $status === null) {
                $mode = 'terkunci';
                $labelStatus = 'Periode TUKIN terkunci';
                $variantStatus = 'warning';
            } elseif (($hariIni['hariLibur'] ?? false) && $status === null) {
                $mode = 'libur-nasional';
                $labelStatus = 'Hari libur';
                $variantStatus = 'info';
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
                'masuk', 'keluar' => 'Nyalakan kamera, minta izin lokasi, lalu hadapkan wajah untuk mengaktifkan tombol.',
                'dl-setengah' => 'DL setengah hari: cukup absen sekali hari ini, tanpa absen pulang.',
                'dl-setengah-selesai' => 'Absen DL setengah hari sudah tercatat; absen pulang tidak diperlukan.',
                'tanpa-shift' => 'Tidak ada shift terjadwal hari ini.',
                'terkunci' => 'Periode TUKIN bulan ini sudah dikunci oleh admin.',
                'libur-nasional' => 'Hari ini hari libur nasional, jadi absen tidak diperlukan.',
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
            data-jam-pulang="{{ $shift['jamPulang'] ?? '' }}"
            data-zona-waktu="{{ config('presensi.zona_waktu', 'Asia/Jakarta') }}"
        >
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Presensi</h1>
                <p class="mt-1 text-sm text-gray-600">Foto dan lokasi divalidasi ulang di server sebelum disimpan. Wajah wajib terdeteksi.</p>
            </div>

            {{-- Kamera dan tombol absen ditaruh paling atas agar tidak perlu scroll. --}}
            <x-card title="Kamera & absen" subtitle="Nyalakan kamera, hadapkan wajah, lalu tekan tombol absen.">
                <div class="flex flex-col gap-3">
                    <div class="relative aspect-video max-h-72 overflow-hidden rounded-md bg-gray-900">
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
                    <x-alert id="pesan-model" variant="danger" class="hidden" role="alert">
                        Deteksi wajah wajib aktif. Muat ulang model deteksi lalu coba lagi.
                    </x-alert>
                    <div id="wadah-muat-model" class="hidden">
                        <x-button type="button" id="tombol-muat-model" variant="secondary" size="sm">Muat ulang model wajah</x-button>
                    </div>

                    <form id="form-presensi" method="POST" action="{{ route('presensi.store') }}" class="flex flex-col gap-3">
                        @csrf

                        <input type="hidden" name="jenis" id="input-jenis" value="{{ $mode === 'keluar' ? 'keluar' : 'masuk' }}">
                        <input type="hidden" name="foto" id="input-foto">
                        <input type="hidden" name="koordinat" id="input-koordinat">
                        <input type="hidden" name="akurasi" id="input-akurasi">
                        <input type="hidden" name="konfirmasi_pulang_cepat" id="input-konfirmasi-pulang-cepat" value="0">

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
                </div>
            </x-card>

            <x-card title="Status hari ini" :subtitle="$hariIni['labelTanggal']">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :variant="$variantStatus">{{ $labelStatus }}</x-badge>

                        @if ($hariIni['terlambat'])
                            <x-badge variant="warning">Terlambat {{ $hariIni['menitTerlambat'] }} menit</x-badge>
                        @endif

                        @if ($shift !== null)
                            <span class="text-xs text-gray-600">
                                {{ $shift['nama'] }} ({{ $shift['kode'] }}) · {{ $shift['jamMasuk'] }}–{{ $shift['jamPulang'] }}
                            </span>
                        @endif
                    </div>

                    @php
                        $penggantian = $hariIni['penggantian'] ?? null;
                        $formatJamGanti = fn (?string $jam): ?string => $jam === null
                            ? null
                            : \Illuminate\Support\Carbon::parse($jam)->format('H:i');
                    @endphp

                    @if ($penggantian !== null && $jamMasuk !== null)
                        @if (! $penggantian['bisaDiganti'])
                            <x-alert variant="danger" title="Tidak bisa diganti">
                                Terlambat {{ $penggantian['menitTerlambat'] }} menit — melebihi batas 30 menit.
                                Potongan keterlambatan tetap dihitung sesuai tingkat TL.
                            </x-alert>
                        @elseif ($penggantian['terpenuhi'])
                            <x-alert variant="success" title="Keterlambatan sudah diganti">
                                Terlambat {{ $penggantian['menitTerlambat'] }} menit dan pulang pukul
                                {{ $formatJamGanti($penggantian['jamSelesai']) }}. Potongan TL tidak berlaku.
                            </x-alert>
                        @elseif ($jamKeluar === null)
                            <x-alert variant="warning" title="Absen pulang pengganti">
                                Terlambat {{ $penggantian['menitTerlambat'] }} menit. Absen pulang antara
                                <strong>{{ $formatJamGanti($penggantian['targetPulang']) }}</strong>–
                                <strong>{{ $formatJamGanti($penggantian['batasPulang']) }}</strong>
                                agar keterlambatan tidak dipotong.
                            </x-alert>
                        @else
                            <x-alert variant="warning" title="Penggantian belum terpenuhi">
                                Pulang pukul {{ $formatJamGanti($penggantian['jamSelesai']) }} belum berada di
                                jendela {{ $formatJamGanti($penggantian['targetPulang']) }}–
                                {{ $formatJamGanti($penggantian['batasPulang']) }}. Potongan TL tetap dihitung.
                            </x-alert>
                        @endif
                    @endif

                    @if ($jamMasuk !== null || $jamKeluar !== null)
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <div class="rounded-md bg-gray-50 px-3 py-2">
                                <dt class="text-xs text-gray-600">Jam masuk</dt>
                                <dd class="font-medium text-gray-900">{{ $jamMasuk ?? '—' }}</dd>
                            </div>
                            <div class="rounded-md bg-gray-50 px-3 py-2">
                                <dt class="text-xs text-gray-600">Jam keluar</dt>
                                <dd class="font-medium text-gray-900">{{ $jamKeluar ?? '—' }}</dd>
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
                    @elseif ($mode === 'terkunci')
                        <x-alert variant="warning">
                            Periode TUKIN bulan ini sudah dikunci. Presensi tidak dapat diubah;
                            hubungi admin bila ada data yang perlu dikoreksi.
                        </x-alert>
                    @elseif ($mode === 'libur-nasional')
                        <x-alert variant="info">
                            Hari ini adalah <strong>hari libur nasional</strong>, jadi absen tidak diperlukan
                            dan tombol absen dinonaktifkan.
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

            <x-card title="Lokasi Anda" subtitle="Lokasi diambil otomatis. Server tetap memvalidasi radius.">
                <div class="flex flex-col gap-3">
                    <div
                        id="peta-presensi"
                        class="h-48 w-full rounded-md border border-gray-200 bg-gray-100"
                        role="img"
                        aria-label="Peta lokasi kantor dan posisi Anda"
                    ></div>

                    <dl class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                        <div class="rounded-md bg-gray-50 px-3 py-2">
                            <dt class="text-xs text-gray-600">Koordinat</dt>
                            <dd id="teks-koordinat" class="text-xs font-medium text-gray-900">Belum diambil</dd>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2">
                            <dt class="text-xs text-gray-600">Jarak ke kantor</dt>
                            <dd id="teks-jarak" class="text-xs font-medium text-gray-900">—</dd>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2">
                            <dt class="text-xs text-gray-600">Akurasi</dt>
                            <dd id="teks-akurasi" class="text-xs font-medium text-gray-900">—</dd>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2">
                            <dt class="text-xs text-gray-600">Status radius</dt>
                            <dd id="teks-radius" class="text-xs font-medium text-gray-900">—</dd>
                        </div>
                    </dl>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-button type="button" id="tombol-lokasi" variant="secondary" size="sm">Minta izin lokasi</x-button>
                        <p class="text-xs text-gray-500">Jika browser belum memberi izin, tekan tombol ini lalu pilih Izinkan.</p>
                    </div>

                    <x-alert id="pesan-lokasi" variant="danger" class="hidden" role="alert"></x-alert>
                </div>
            </x-card>
        </div>

        @push('scripts')
            @vite('resources/js/presensi.js')
        @endpush
    @endif
</x-layouts.pegawai>
