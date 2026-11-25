<x-layouts.admin title="Rekap Keterlambatan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rekap keterlambatan</h1>
            <p class="mt-1 text-sm text-gray-600">Akumulasi keterlambatan per pegawai berdasarkan shift masing-masing.</p>
        </div>

        <x-card title="Filter" subtitle="Pilih bulan untuk rekap bulanan, atau kosongkan bulan untuk akumulasi satu tahun.">
            <form method="GET" action="{{ route('admin.rekap.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="sm:flex-1">
                    <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select
                        id="bulan"
                        name="bulan"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Seluruh bulan (akumulasi tahun)</option>
                        @foreach ($pilihanBulan as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($bulan === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('bulan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                    @if (request()->hasAny(['bulan', 'tahun']))
                        <a
                            href="{{ route('admin.rekap.index') }}"
                            class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                        >Reset</a>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-card>
                <p class="text-xs text-gray-600">Pegawai terlambat</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $ringkasan['pegawaiTerlambat'] }} / {{ $ringkasan['totalPegawai'] }}
                </p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total hari terlambat</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['totalHariTerlambat'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total keterlambatan</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['totalMenitTerlambatTeks'] }}</p>
            </x-card>
        </div>

        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Rekap {{ $labelPeriode }}</h2>
            <p class="text-xs text-gray-600">{{ count($baris) }} pegawai</p>
        </div>

        @if ($baris === [])
            <x-alert variant="info">Belum ada data pegawai.</x-alert>
        @else
            <x-table>
                <thead>
                    <tr>
                        <x-table.th align="center">No</x-table.th>
                        <x-table.th>NIP</x-table.th>
                        <x-table.th>Nama Pegawai</x-table.th>
                        <x-table.th>Departemen</x-table.th>
                        <x-table.th align="center">Hari terlambat</x-table.th>
                        <x-table.th align="right">Total keterlambatan</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($baris as $item)
                        <tr>
                            <x-table.td align="center">{{ $loop->iteration }}</x-table.td>
                            <x-table.td>{{ $item['nip'] }}</x-table.td>
                            <x-table.td>
                                <span class="font-medium text-gray-900">{{ $item['nama'] }}</span>
                            </x-table.td>
                            <x-table.td>{{ $item['departemen'] ?? '—' }}</x-table.td>
                            <x-table.td align="center">{{ $item['hariTerlambat'] }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-medium text-gray-900">{{ $item['totalMenitTerlambatTeks'] }}</span>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </div>
</x-layouts.admin>
