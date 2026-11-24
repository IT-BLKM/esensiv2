<x-layouts.admin title="Konfigurasi">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Konfigurasi jam kerja</h1>
            <p class="mt-1 text-sm text-gray-600">
                Atur shift per hari untuk setiap pegawai. Baris kosong berarti tidak ada shift pada hari itu.
            </p>
        </div>

        <x-alert variant="info">
            Pengaturan titik lokasi dan radius absen menyusul di issue #30.
        </x-alert>

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Pegawai</x-table.th>
                        @foreach ($daftarHari as $hari)
                            <x-table.th align="center">{{ $hari }}</x-table.th>
                        @endforeach
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($daftarPegawai as $pegawai)
                        <tr>
                            <x-table.td>
                                <div class="font-medium text-gray-900">{{ $pegawai->nama }}</div>
                                <div class="text-xs text-gray-500">{{ $pegawai->nip }}</div>
                            </x-table.td>
                            @foreach ($daftarHari as $hari)
                                @php
                                    $konfigurasi = $pegawai->konfigurasiJamKerja->firstWhere('hari', $hari);
                                    $jamKerja = $konfigurasi?->jamKerja;
                                @endphp
                                <x-table.td align="center">
                                    @if ($jamKerja)
                                        <x-badge variant="brand" title="{{ $jamKerja->nama }}">{{ $jamKerja->kode }}</x-badge>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </x-table.td>
                            @endforeach
                            <x-table.td align="right">
                                <a
                                    href="{{ route('admin.konfigurasi.edit', $pegawai) }}"
                                    class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                >Atur jadwal</a>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <x-table.td colspan="{{ count($daftarHari) + 2 }}">Belum ada pegawai.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
        </x-card>
    </div>
</x-layouts.admin>
