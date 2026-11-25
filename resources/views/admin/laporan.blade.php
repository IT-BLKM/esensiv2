<x-layouts.admin title="Laporan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Laporan bulanan</h1>
            <p class="mt-1 text-sm text-gray-600">Pilih pegawai dan periode untuk melihat daftar harian serta rekap kehadiran.</p>
        </div>

        <x-card title="Filter" subtitle="Pilih pegawai, bulan, dan tahun laporan.">
            <form method="GET" action="{{ route('admin.laporan.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="lg:flex-[2]">
                    <label for="id_pegawai" class="block text-sm font-medium text-gray-700">Pegawai</label>
                    <select
                        id="id_pegawai"
                        name="id_pegawai"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Pilih pegawai</option>
                        @foreach ($daftarPegawai as $item)
                            <option value="{{ $item->id }}" @selected($idPegawai === $item->id)>
                                {{ $item->nama }} ({{ $item->nip }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_pegawai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select
                        id="bulan"
                        name="bulan"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        @foreach ($pilihanBulan as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($bulan === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('bulan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <select
                        id="tahun"
                        name="tahun"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        @foreach ($pilihanTahun as $nilai)
                            <option value="{{ $nilai }}" @selected($tahun === $nilai)>{{ $nilai }}</option>
                        @endforeach
                    </select>
                    @error('tahun')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                    @if (request()->hasAny(['id_pegawai', 'bulan', 'tahun']))
                        <a
                            href="{{ route('admin.laporan.index') }}"
                            class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                        >Reset</a>
                    @endif
                </div>
            </form>
        </x-card>

        @if ($pegawai === null)
            <x-alert variant="info" title="Pilih pegawai">
                Pilih pegawai pada filter di atas untuk menampilkan laporan bulanan.
            </x-alert>
        @else
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">{{ $pegawai->nama }}</h2>
                    <p class="text-sm text-gray-600">NIP {{ $pegawai->nip }} · {{ $labelPeriode }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <x-card>
                    <p class="text-xs text-gray-600">Hadir</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['tepatWaktu'] + $ringkasan['terlambat'] }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">Tepat waktu: {{ $ringkasan['tepatWaktu'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs text-gray-600">Terlambat</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['terlambat'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs text-gray-600">Cuti</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['cuti'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs text-gray-600">Sakit</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['sakit'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs text-gray-600">Dinas Luar</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['dl'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs text-gray-600">Total terlambat</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['totalMenitTerlambatTeks'] }}</p>
                </x-card>
            </div>

            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-900">Daftar harian</h3>
                <p class="text-xs text-gray-600">{{ count($baris) }} baris · {{ $ringkasan['totalHari'] }} total</p>
            </div>

            @if ($baris === [])
                <x-alert variant="info">
                    Tidak ada presensi pada {{ $labelPeriode }}.
                </x-alert>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <x-table.th>Tanggal</x-table.th>
                            <x-table.th>Shift</x-table.th>
                            <x-table.th align="center">Masuk</x-table.th>
                            <x-table.th align="center">Pulang</x-table.th>
                            <x-table.th align="center">Status</x-table.th>
                            <x-table.th align="right">Keterlambatan</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($baris as $item)
                            @php
                                $variantStatus = match ($item['status']) {
                                    'hadir' => 'success',
                                    'cuti' => 'info',
                                    'sakit' => 'warning',
                                    'dl' => 'brand',
                                    default => 'neutral',
                                };

                                $penggantian = $item['penggantian'];
                            @endphp

                            <tr>
                                <x-table.td>
                                    <span class="font-medium text-gray-900">{{ $item['labelTanggal'] }}</span>
                                    <span class="block text-xs text-gray-600">{{ $item['hari'] }}</span>
                                </x-table.td>
                                <x-table.td>{{ $item['shift'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">{{ $item['jamMasuk'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">{{ $item['jamKeluar'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">
                                    <x-badge :variant="$variantStatus">{{ $item['statusLabel'] }}</x-badge>
                                </x-table.td>
                                <x-table.td align="right">
                                    @if ($item['status'] === 'hadir')
                                        @if ($item['menitTerlambat'] > 0)
                                            @if ($penggantian !== null && $penggantian['terpenuhi'])
                                                <x-badge variant="success">Terlambat {{ $item['menitTerlambat'] }} menit — diganti</x-badge>
                                            @elseif ($penggantian !== null && ! $penggantian['bisaDiganti'])
                                                <x-badge variant="danger">Terlambat {{ $item['menitTerlambat'] }} menit — tidak bisa diganti</x-badge>
                                            @else
                                                <x-badge variant="warning">Terlambat {{ $item['menitTerlambat'] }} menit</x-badge>
                                            @endif
                                        @else
                                            <span class="text-gray-600">Tepat waktu</span>
                                        @endif
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        @endif
    </div>
</x-layouts.admin>
