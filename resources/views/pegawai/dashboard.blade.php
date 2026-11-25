<x-layouts.pegawai title="Dashboard">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard pegawai</h1>
            <p class="mt-1 text-sm text-gray-600">Rekap kehadiran hari ini dan keterlambatan Anda.</p>
        </div>

        @if ($rekap === null)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai, jadi rekap belum bisa dihitung.
                Hubungi administrator untuk melengkapi data.
            </x-alert>
        @else
            @php
                $hariIni = $rekap['hariIni'];
                $bulanIni = $rekap['bulanIni'];
                $tahunIni = $rekap['tahunIni'];

                $variantStatus = match ($hariIni['status']) {
                    'hadir' => 'success',
                    'cuti' => 'info',
                    'sakit' => 'warning',
                    'dl' => 'brand',
                    default => 'neutral',
                };

                $rincianBulan = [
                    ['label' => 'Hadir', 'nilai' => $bulanIni['tepatWaktu'], 'keterangan' => 'Masuk tepat waktu'],
                    ['label' => 'Terlambat', 'nilai' => $bulanIni['terlambat'], 'keterangan' => 'Masuk melewati batas shift'],
                    ['label' => 'Cuti', 'nilai' => $bulanIni['cuti'], 'keterangan' => 'Cuti disetujui'],
                    ['label' => 'Sakit', 'nilai' => $bulanIni['sakit'], 'keterangan' => 'Sakit disetujui'],
                    ['label' => 'DL', 'nilai' => $bulanIni['dl'], 'keterangan' => 'Dinas luar'],
                ];
            @endphp

            <x-card title="Presensi hari ini" :subtitle="$hariIni['labelTanggal']">
                <div class="flex flex-col gap-3">
                    @if ($hariIni['shift'] === null)
                        <p class="text-sm text-gray-600">Tidak ada jadwal shift untuk hari ini.</p>
                    @else
                        <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between gap-4 pb-2">
                                <dt class="text-gray-600">Shift</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $hariIni['shift']['nama'] }}
                                    <span class="font-normal text-gray-600">({{ $hariIni['shift']['kode'] }})</span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <dt class="text-gray-600">Jadwal</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $hariIni['shift']['jamMasuk'] }} &ndash; {{ $hariIni['shift']['jamPulang'] }}
                                </dd>
                            </div>
                        </dl>
                    @endif

                    @if ($hariIni['status'] === null)
                        <p class="text-sm text-gray-600">Belum ada presensi tercatat hari ini.</p>
                    @else
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :variant="$variantStatus">{{ $hariIni['statusLabel'] }}</x-badge>

                            @if ($hariIni['terlambat'])
                                <x-badge variant="warning">Terlambat {{ $hariIni['menitTerlambat'] }} menit</x-badge>
                            @endif
                        </div>

                        <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between gap-4 pb-2">
                                <dt class="text-gray-600">Jam masuk</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $hariIni['jamMasuk'] ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <dt class="text-gray-600">Jam keluar</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $hariIni['jamKeluar'] ?? '—' }}</dd>
                            </div>
                        </dl>
                    @endif
                </div>
            </x-card>

            @if ($sisaCuti !== null)
                <x-card title="Sisa cuti tahunan" subtitle="Termasuk tabungan dua tahun sebelumnya. Hari libur tidak mengurangi kuota.">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-md border border-brand-200 bg-brand-50 px-3 py-2">
                            <p class="text-xs text-brand-800">Total tersedia</p>
                            <p class="mt-1 text-lg font-semibold text-brand-900">{{ $sisaCuti['totalTersedia'] }} hari</p>
                        </div>
                        <div class="rounded-md border border-gray-200 px-3 py-2">
                            <p class="text-xs text-gray-600">Sisa kuota {{ $sisaCuti['tahun'] }}</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $sisaCuti['sisaKuota'] }} hari</p>
                        </div>
                        <div class="rounded-md border border-gray-200 px-3 py-2">
                            <p class="text-xs text-gray-600">Sudah terpakai</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $sisaCuti['terpakai'] }} hari</p>
                        </div>
                        <div class="rounded-md border border-gray-200 px-3 py-2">
                            <p class="text-xs text-gray-600">Kuota tahunan</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $sisaCuti['kuota'] }} hari</p>
                        </div>
                    </div>

                    @if ($sisaCuti['tabungan'] !== [])
                        <div class="mt-3 flex flex-col gap-1 rounded-md bg-gray-50 px-3 py-2 text-sm">
                            @foreach ($sisaCuti['tabungan'] as $tabungan)
                                <p class="flex items-baseline justify-between gap-3">
                                    <span class="text-gray-600">Tabungan {{ $tabungan['tahun_sumber'] }}</span>
                                    <span class="font-medium text-gray-900">{{ $tabungan['sisa'] }} hari</span>
                                </p>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('pengajuan.create') }}"
                            class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-800"
                        >Ajukan cuti</a>
                        <p class="text-xs text-gray-500">Sisa dihitung dari baris presensi hari kerja dan saldo tabungan.</p>
                    </div>
                </x-card>
            @endif

            <x-card
                title="Rekap {{ $bulanIni['label'] }}"
                subtitle="Hadir berarti masuk tepat waktu; terlambat dihitung sebagai angka terpisah."
            >
                <dl class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                    @foreach ($rincianBulan as $item)
                        <div class="rounded-md border border-gray-200 px-3 py-2">
                            <dt class="text-xs text-gray-600" title="{{ $item['keterangan'] }}">{{ $item['label'] }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $item['nilai'] }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-3 flex flex-col gap-1 rounded-md bg-gray-50 px-3 py-2 text-sm">
                    <p class="flex items-baseline justify-between gap-3">
                        <span class="text-gray-600">Terlambat bulan ini</span>
                        <span class="text-right font-semibold text-gray-900">{{ $bulanIni['totalMenitTerlambatTeks'] }}</span>
                    </p>
                    <p class="flex items-baseline justify-between gap-3">
                        <span class="text-gray-600">Terlambat tahun {{ $tahunIni['tahun'] }}</span>
                        <span class="text-right font-semibold text-gray-900">
                            {{ $tahunIni['totalMenitTerlambatTeks'] }} ({{ $tahunIni['hariTerlambat'] }} hari)
                        </span>
                    </p>
                </div>

                @if ($bulanIni['totalHari'] === 0)
                    <x-alert variant="info" class="mt-3">Belum ada presensi tercatat bulan ini.</x-alert>
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.pegawai>
