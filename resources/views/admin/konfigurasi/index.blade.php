<x-layouts.admin title="Konfigurasi">
    @php
        $latitude = (string) config('presensi.latitude');
        $longitude = (string) config('presensi.longitude');
        $radius = (int) config('presensi.radius');
        $lokasiSah = $latitude !== '' && $longitude !== '' && $radius > 0;
    @endphp

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Konfigurasi</h1>
            <p class="mt-1 text-sm text-gray-600">
                Jam kerja per pegawai, titik lokasi kantor, dan radius absen.
            </p>
        </div>

        <x-card
            title="Lokasi & radius absen"
            subtitle="Hanya-baca — sumbernya config/presensi.php, diisi lewat .env"
        >
            @unless ($lokasiSah)
                <x-alert variant="danger" title="Konfigurasi belum lengkap" class="mb-4">
                    Salah satu dari <code class="rounded bg-gray-100 px-1">PRESENSI_LAT</code>,
                    <code class="rounded bg-gray-100 px-1">PRESENSI_LONG</code>, atau
                    <code class="rounded bg-gray-100 px-1">PRESENSI_RADIUS</code> kosong/nol.
                    Lengkapi di <code class="rounded bg-gray-100 px-1">.env</code> lalu restart server.
                </x-alert>
            @endunless

            <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Lintang (latitude)</dt>
                    <dd class="text-right font-medium text-gray-900">
                        {{ $latitude !== '' ? $latitude : '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Bujur (longitude)</dt>
                    <dd class="text-right font-medium text-gray-900">
                        {{ $longitude !== '' ? $longitude : '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Radius absen</dt>
                    <dd class="text-right font-medium text-gray-900">
                        {{ $radius > 0 ? number_format($radius, 0, ',', '.').' m' : '—' }}
                        @if ($radius >= 1000)
                            <span class="text-gray-500">({{ number_format($radius / 1000, 0, ',', '.') }} km)</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                <p class="text-xs text-gray-500">
                    Perubahan dilakukan di <code class="rounded bg-gray-100 px-1">.env</code>,
                    bukan lewat halaman ini.
                </p>
                @if ($lokasiSah)
                    <a
                        href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($latitude.','.$longitude) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                    >
                        Buka di peta
                    </a>
                @endif
            </div>
        </x-card>

        <x-card title="Jadwal jam kerja per pegawai">
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
