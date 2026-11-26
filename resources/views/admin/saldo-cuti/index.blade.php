<x-layouts.admin title="Saldo Cuti">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Saldo awal cuti tahunan</h1>
            <p class="mt-1 text-sm text-gray-600">
                Isi tabungan cuti {{ $tahunLalu }} dan {{ $duaTahunLalu }} untuk migrasi awal.
                Pemakaian {{ $tahun }} tetap dihitung dari presensi dan pengajuan yang disetujui.
            </p>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-alert variant="info">
            Kuota tahunan {{ $cuti->kuota }} hari ditambah tabungan maksimal {{ $cuti->maks_bawa }} hari
            per tahun sumber. Total tersedia maksimal {{ (int) $cuti->kuota + 2 * (int) $cuti->maks_bawa }} hari.
        </x-alert>

        <x-card>
            <form method="GET" action="{{ route('admin.saldo-cuti.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="w-full sm:w-72">
                    <label for="q" class="block text-sm font-medium text-gray-700">Cari pegawai</label>
                    <x-input
                        id="q"
                        name="q"
                        :value="$kataKunci"
                        placeholder="Nama atau NIP"
                        class="mt-1"
                    />
                </div>
                <x-button type="submit" variant="secondary">Cari</x-button>
                @if ($kataKunci !== '')
                    <a
                        href="{{ route('admin.saldo-cuti.index') }}"
                        class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Reset</a>
                @endif
            </form>
        </x-card>

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th align="center">No</x-table.th>
                        <x-table.th>Pegawai</x-table.th>
                        <x-table.th align="center">Tabungan {{ $duaTahunLalu }}</x-table.th>
                        <x-table.th align="center">Tabungan {{ $tahunLalu }}</x-table.th>
                        <x-table.th align="center">Terpakai {{ $tahun }}</x-table.th>
                        <x-table.th align="center">Sisa kuota {{ $tahun }}</x-table.th>
                        <x-table.th align="center">Total tersedia</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($baris as $item)
                        <tr>
                            <x-table.td align="center">{{ $loop->iteration }}</x-table.td>
                            <x-table.td>
                                <div class="font-medium text-gray-900">{{ $item['pegawai']->nama }}</div>
                                <div class="text-xs text-gray-500">{{ $item['pegawai']->nip }}</div>
                            </x-table.td>
                            <x-table.td align="center">{{ $item['saldo_dua_tahun_lalu'] }} hari</x-table.td>
                            <x-table.td align="center">{{ $item['saldo_tahun_lalu'] }} hari</x-table.td>
                            <x-table.td align="center">{{ $item['terpakai'] }} hari</x-table.td>
                            <x-table.td align="center">{{ $item['sisaKuota'] }} hari</x-table.td>
                            <x-table.td align="center">
                                <span class="font-semibold text-gray-900">{{ $item['totalTersedia'] }} hari</span>
                            </x-table.td>
                            <x-table.td align="right">
                                <a
                                    href="{{ route('admin.saldo-cuti.edit', $item['pegawai']) }}"
                                    class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                >Atur saldo</a>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <x-table.td colspan="8">Belum ada pegawai yang cocok.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-gray-600">Menampilkan {{ $baris->count() }} pegawai.</p>
        </x-card>
    </div>
</x-layouts.admin>
