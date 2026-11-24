<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Referensi Komponen — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 p-8 text-gray-900">
    <div class="mx-auto flex max-w-5xl flex-col gap-10">

        <header class="flex flex-col gap-2">
            <p class="text-sm font-medium text-brand-700">Design system {{ config('app.name') }}</p>
            <h1 class="text-2xl font-semibold">Referensi Komponen</h1>
            <p class="max-w-3xl text-sm text-gray-600">
                Halaman hidup untuk seluruh anggota tim. Sebelum menulis gaya baru di halaman fitur,
                cari dulu apakah komponennya sudah ada di sini. Aturan lengkap ada di
                <code class="rounded bg-gray-200 px-1">docs/konvensi-ui.md</code>.
            </p>
        </header>

        {{-- Warna --}}
        <section class="flex flex-col gap-4">
            <div>
                <h2 class="text-lg font-semibold">1. Warna</h2>
                <p class="text-sm text-gray-600">
                    Semua warna berasal dari logo Kemenkes. <strong>Step 700–950</strong> boleh dipakai sebagai teks
                    (kontras &ge; 4.5:1), <strong>step 600</strong> hanya untuk border/elemen UI (kontras &ge; 3:1).
                    <strong>Step 500</strong> dan ke bawah hanya untuk latar.
                </p>
            </div>

            @php
                /*
                 * Kelas ditulis literal di sini — Tailwind hanya memindai teks statis,
                 * jadi 'bg-brand-50' harus muncul apa adanya agar utilitasnya dibuat.
                 */
                $palettes = [
                    'brand' => [
                        'label' => 'Brand (utama) — #00B9AD',
                        'steps' => [
                            50 => 'bg-brand-50', 100 => 'bg-brand-100', 200 => 'bg-brand-200', 300 => 'bg-brand-300',
                            400 => 'bg-brand-400', 500 => 'bg-brand-500', 600 => 'bg-brand-600', 700 => 'bg-brand-700',
                            800 => 'bg-brand-800', 900 => 'bg-brand-900', 950 => 'bg-brand-950',
                        ],
                    ],
                    'accent' => [
                        'label' => 'Accent (sorotan) — #CDDC29',
                        'steps' => [
                            50 => 'bg-accent-50', 100 => 'bg-accent-100', 200 => 'bg-accent-200', 300 => 'bg-accent-300',
                            400 => 'bg-accent-400', 500 => 'bg-accent-500', 600 => 'bg-accent-600', 700 => 'bg-accent-700',
                            800 => 'bg-accent-800', 900 => 'bg-accent-900', 950 => 'bg-accent-950',
                        ],
                    ],
                    'info' => [
                        'label' => 'Info (sekunder) — #60C0D0',
                        'steps' => [
                            50 => 'bg-info-50', 100 => 'bg-info-100', 200 => 'bg-info-200', 300 => 'bg-info-300',
                            400 => 'bg-info-400', 500 => 'bg-info-500', 600 => 'bg-info-600', 700 => 'bg-info-700',
                            800 => 'bg-info-800', 900 => 'bg-info-900', 950 => 'bg-info-950',
                        ],
                    ],
                ];
            @endphp

            @foreach ($palettes as $palette)
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-medium text-gray-700">{{ $palette['label'] }}</p>
                    <div class="grid grid-cols-6 gap-2 sm:grid-cols-11">
                        @foreach ($palette['steps'] as $step => $swatch)
                            <div class="flex flex-col gap-1">
                                <div class="h-10 rounded border border-black/10 {{ $swatch }}"></div>
                                <span class="text-[10px] text-gray-500">{{ $step }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Tombol --}}
        <section class="flex flex-col gap-4">
            <div>
                <h2 class="text-lg font-semibold">2. Tombol</h2>
                <p class="text-sm text-gray-600">Selalu lewat <code class="rounded bg-gray-200 px-1">&lt;x-button&gt;</code>, jangan menulis utility sendiri.</p>
            </div>

            <x-card title="Variant">
                <div class="flex flex-wrap items-center gap-3">
                    <x-button>Primary</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="danger">Danger</x-button>
                    <x-button disabled>Disabled</x-button>
                </div>
            </x-card>

            <x-card title="Ukuran">
                <div class="flex flex-wrap items-center gap-3">
                    <x-button size="sm">Small</x-button>
                    <x-button size="md">Medium</x-button>
                    <x-button size="lg">Large</x-button>
                </div>
            </x-card>
        </section>

        {{-- Badge --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">3. Badge</h2>
            <x-card>
                <div class="flex flex-wrap gap-2">
                    <x-badge variant="brand">Brand</x-badge>
                    <x-badge variant="info">Info</x-badge>
                    <x-badge variant="accent">Accent</x-badge>
                    <x-badge variant="success">Hadir</x-badge>
                    <x-badge variant="warning">Terlambat</x-badge>
                    <x-badge variant="danger">Ditolak</x-badge>
                    <x-badge>Pending</x-badge>
                </div>
            </x-card>
        </section>

        {{-- Alert --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">4. Alert</h2>
            <div class="flex flex-col gap-3">
                <x-alert variant="success" title="Berhasil">Data presensi tersimpan.</x-alert>
                <x-alert variant="info" title="Informasi">Radius kantor 2.500 meter dari titik lokasi.</x-alert>
                <x-alert variant="warning" title="Perhatian">Saldo cuti tinggal 2 hari.</x-alert>
                <x-alert variant="danger" title="Gagal">Foto wajah tidak terdeteksi.</x-alert>
            </div>
        </section>

        {{-- Form --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">5. Form</h2>
            <x-card>
                <div class="flex max-w-md flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="nip" class="text-sm font-medium text-gray-700">NIP</label>
                        <x-input id="nip" name="nip" placeholder="18 digit angka" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="nama" class="text-sm font-medium text-gray-700">Nama pegawai</label>
                        <x-input id="nama" name="nama" value="Budi Santoso" invalid />
                        <p class="text-xs text-red-600">Nama wajib diisi.</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-medium text-gray-700">Password baru</label>
                        <x-input id="password" name="password" type="password" disabled value="123456" />
                        <p class="text-xs text-gray-500">Minimal 8 karakter.</p>
                    </div>
                </div>
            </x-card>
        </section>

        {{-- Tabel --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">6. Tabel</h2>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>NIP</x-table.th>
                        <x-table.th>Nama</x-table.th>
                        <x-table.th align="center">Status</x-table.th>
                        <x-table.th align="right">Keterlambatan</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <x-table.td>199302032022032001</x-table.td>
                        <x-table.td>Budi Santoso</x-table.td>
                        <x-table.td align="center"><x-badge variant="success">Hadir</x-badge></x-table.td>
                        <x-table.td align="right">0 menit</x-table.td>
                    </tr>
                    <tr>
                        <x-table.td>198009082003122003</x-table.td>
                        <x-table.td>Ani Wijaya</x-table.td>
                        <x-table.td align="center"><x-badge variant="warning">Terlambat</x-badge></x-table.td>
                        <x-table.td align="right">15 menit</x-table.td>
                    </tr>
                </tbody>
            </x-table>
        </section>

        {{-- Ikon --}}
        <section class="flex flex-col gap-4">
            <div>
                <h2 class="text-lg font-semibold">7. Ikon</h2>
                <p class="text-sm text-gray-600">
                    Ikon stroke milik sendiri lewat <code class="rounded bg-gray-200 px-1">&lt;x-icon name="..."&gt;</code>
                    — tidak ada dependensi ikon eksternal dan tidak ada SVG tempel per halaman.
                    Nama baru ditambahkan di <code class="rounded bg-gray-200 px-1">resources/views/components/icon.blade.php</code>
                    sekaligus di daftar ini.
                </p>
            </div>

            <x-card>
                <div class="flex flex-wrap gap-x-6 gap-y-4 text-gray-700">
                    @foreach (['dashboard', 'pegawai', 'departemen', 'jam', 'cuti', 'monitoring', 'pengajuan', 'laporan', 'konfigurasi', 'user', 'menu', 'logout'] as $nama)
                        <div class="flex w-24 flex-col items-center gap-1.5">
                            <x-icon :name="$nama" size="lg" />
                            <span class="text-center text-xs text-gray-600">{{ $nama }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </section>

        {{-- Layout --}}
        <section class="flex flex-col gap-4">
            <div>
                <h2 class="text-lg font-semibold">8. Layout halaman</h2>
                <p class="text-sm text-gray-600">
                    Seluruh halaman admin memakai <code class="rounded bg-gray-200 px-1">&lt;x-layouts.admin title="..."&gt;</code>
                    (header, sidebar menu, pesan sesi, footer). Lihat langsung dengan masuk sebagai admin ke
                    <a class="font-medium text-brand-700 underline underline-offset-4" href="{{ route('admin.dashboard') }}">{{ route('admin.dashboard') }}</a>.
                </p>
            </div>
        </section>

        <footer class="border-t border-gray-200 pt-4 text-xs text-gray-500">
            Halaman referensi internal — tidak diekspos pada produksi.
        </footer>
    </div>
</body>
</html>
