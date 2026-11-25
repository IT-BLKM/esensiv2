<x-layouts.admin title="Atur Jam Kerja">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Atur jam kerja {{ $pegawai->nama }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                Pilih shift untuk tiap hari. Kosongkan hari libur — perubahan disimpan sebagai update, bukan baris baru.
            </p>
        </div>

        @if ($errors->any())
            <x-alert variant="danger" title="Jadwal belum tersimpan">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card title="Ringkasan jadwal" subtitle="Tujuh hari terakhir yang tersimpan untuk pegawai ini.">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($daftarHari as $hari)
                    @php
                        $jamKerja = $jadwal->get($hari)?->jamKerja;
                    @endphp
                    <div class="rounded-md border border-gray-200 px-3 py-2">
                        <p class="text-xs font-medium text-gray-500">{{ $hari }}</p>
                        <p class="mt-0.5 text-sm text-gray-900">{{ $jamKerja?->nama ?? 'Tidak ada shift' }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <form method="POST" action="{{ route('admin.konfigurasi.update', $pegawai) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($daftarHari as $index => $hari)
                        @php
                            $terpilih = old(
                                'jadwal.'.$index.'.id_jam_kerja',
                                $jadwal->get($hari)?->id_jam_kerja,
                            );
                        @endphp
                        <div>
                            <input type="hidden" name="jadwal[{{ $index }}][hari]" value="{{ $hari }}" />
                            <label for="jam-{{ $index }}" class="block text-sm font-medium text-gray-700">{{ $hari }}</label>
                            <select
                                id="jam-{{ $index }}"
                                name="jadwal[{{ $index }}][id_jam_kerja]"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                            >
                                <option value="">Pilih Jam Kerja</option>
                                @foreach ($daftarJamKerja as $jamKerja)
                                    <option
                                        value="{{ $jamKerja->id }}"
                                        @selected((string) $terpilih === (string) $jamKerja->id)
                                    >{{ $jamKerja->kode }} — {{ $jamKerja->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="submit">Simpan jadwal</x-button>
                    <a
                        href="{{ route('admin.konfigurasi.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Kembali</a>
                </div>
            </form>
        </x-card>

        <x-card title="Shift tersedia">
            <div class="flex flex-wrap gap-2">
                @forelse ($daftarJamKerja as $jamKerja)
                    <x-badge variant="neutral" title="{{ $jamKerja->jam_masuk }} — {{ $jamKerja->jam_pulang }}">
                        {{ $jamKerja->kode }} — {{ $jamKerja->nama }}
                    </x-badge>
                @empty
                    <p class="text-sm text-gray-600">Belum ada jam kerja. Buat dulu di halaman Jam Kerja.</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-layouts.admin>
