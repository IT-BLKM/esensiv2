<x-layouts.admin title="Dashboard">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Dashboard admin</h1>
                <p class="mt-1 text-sm text-gray-600">Ringkasan keterlambatan {{ $labelBulan }} dan akumulasi tahun {{ $tahun }}.</p>
            </div>

            <a
                href="{{ route('admin.rekap.index') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Buka rekap keterlambatan</a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-card>
                <p class="text-xs text-gray-600">Total pegawai</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasanBulan['totalPegawai'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Pegawai terlambat bulan ini</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasanBulan['pegawaiTerlambat'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total keterlambatan bulan ini</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasanBulan['totalMenitTerlambatTeks'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Akumulasi tahun {{ $tahun }}</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasanTahun['totalMenitTerlambatTeks'] }}</p>
            </x-card>
        </div>

        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Terlambat bulan ini</h2>
            <p class="text-xs text-gray-600">5 pegawai dengan total menit tertinggi</p>
        </div>

        @if ($terlambatBulanIni === [])
            <x-alert variant="success">Tidak ada keterlambatan pada {{ $labelBulan }}.</x-alert>
        @else
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Pegawai</x-table.th>
                        <x-table.th>Departemen</x-table.th>
                        <x-table.th align="center">Hari terlambat</x-table.th>
                        <x-table.th align="right">Total keterlambatan</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($terlambatBulanIni as $item)
                        <tr>
                            <x-table.td>
                                <span class="font-medium text-gray-900">{{ $item['nama'] }}</span>
                                <span class="block text-xs text-gray-600">{{ $item['nip'] }}</span>
                            </x-table.td>
                            <x-table.td>{{ $item['departemen'] ?? '—' }}</x-table.td>
                            <x-table.td align="center">{{ $item['hariTerlambat'] }} hari</x-table.td>
                            <x-table.td align="right" class="font-medium text-gray-900">{{ $item['totalMenitTerlambatTeks'] }}</x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </div>
</x-layouts.admin>
