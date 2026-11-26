<x-layouts.admin title="Konfigurasi">
    @php
        $latitude = old('latitude', $lokasi['latitude']);
        $longitude = old('longitude', $lokasi['longitude']);
        $radiusInput = old('radius', (string) $lokasi['radius']);
        $radius = (int) $radiusInput;
        $lokasiSah = is_numeric($latitude) && is_numeric($longitude) && $radius > 0;
    @endphp

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Konfigurasi</h1>
            <p class="mt-1 text-sm text-gray-600">
                Jam kerja per pegawai, titik lokasi kantor, dan radius absen.
            </p>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card
            title="Lokasi & radius absen"
            subtitle="Dipakai server untuk memvalidasi setiap absen. Simpan di sini agar tidak perlu mengubah .env."
        >
            <form method="POST" action="{{ route('admin.konfigurasi.lokasi.update') }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700">Lintang (latitude)</label>
                        <input
                            id="latitude"
                            name="latitude"
                            type="number"
                            step="any"
                            min="-90"
                            max="90"
                            required
                            value="{{ $latitude }}"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                        @error('latitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700">Bujur (longitude)</label>
                        <input
                            id="longitude"
                            name="longitude"
                            type="number"
                            step="any"
                            min="-180"
                            max="180"
                            required
                            value="{{ $longitude }}"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                        @error('longitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="radius" class="block text-sm font-medium text-gray-700">Radius absen (meter)</label>
                        <input
                            id="radius"
                            name="radius"
                            type="number"
                            step="1"
                            min="1"
                            max="100000"
                            required
                            value="{{ $radiusInput }}"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                        @error('radius')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">
                        Radius {{ number_format(max($radius, 0), 0, ',', '.') }} m
                        @if ($radius >= 1000)
                            ({{ number_format($radius / 1000, 0, ',', '.') }} km)
                        @endif
                        · nilai awal diambil dari <code class="rounded bg-gray-100 px-1">config/presensi.php</code>
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        @if ($lokasiSah)
                            <a
                                href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode((string) $latitude.','.(string) $longitude) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                            >
                                Buka di peta
                            </a>
                        @endif

                        <x-button type="submit" size="sm">Simpan lokasi</x-button>
                    </div>
                </div>
            </form>
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
