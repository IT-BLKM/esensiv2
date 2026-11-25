<x-layouts.pegawai title="Histori">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Histori presensi</h1>
            <p class="mt-1 text-sm text-gray-600">Catatan kehadiran bulanan Anda, bisa difilter per bulan dan tahun.</p>
        </div>

        @if (! $adaPegawai)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai, jadi histori belum bisa ditampilkan.
                Hubungi administrator untuk melengkapi data.
            </x-alert>
        @else
            <x-card title="Filter periode" subtitle="Pilih bulan dan tahun yang ingin dilihat.">
                <form method="GET" action="{{ route('histori.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="sm:flex-1">
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
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:flex-1">
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
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                </form>
            </x-card>

            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-900">Histori {{ $labelPeriode }}</h2>
                <p class="text-xs text-gray-600">{{ count($histori) }} baris</p>
            </div>

            @if ($histori === [])
                <x-alert variant="info">
                    Belum ada presensi pada {{ $labelPeriode }}.
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
                        @foreach ($histori as $baris)
                            @php
                                $variantStatus = match ($baris['status']) {
                                    'hadir' => 'success',
                                    'cuti' => 'info',
                                    'sakit' => 'warning',
                                    'dl' => 'brand',
                                    default => 'neutral',
                                };
                            @endphp

                            <tr>
                                <x-table.td>
                                    <span class="font-medium text-gray-900">{{ $baris['labelTanggal'] }}</span>
                                    <span class="block text-xs text-gray-600">{{ $baris['hari'] }}</span>
                                </x-table.td>
                                <x-table.td>{{ $baris['shift'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">{{ $baris['jamMasuk'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">{{ $baris['jamKeluar'] ?? '—' }}</x-table.td>
                                <x-table.td align="center">
                                    <x-badge :variant="$variantStatus">{{ $baris['statusLabel'] }}</x-badge>
                                </x-table.td>
                                <x-table.td align="right">
                                    @if ($baris['status'] === 'hadir')
                                        @if ($baris['menitTerlambat'] > 0)
                                            <x-badge variant="warning">Terlambat {{ $baris['menitTerlambat'] }} menit</x-badge>
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
</x-layouts.pegawai>
