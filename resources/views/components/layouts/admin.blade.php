@props([
    'title' => null,
])

@php
    $pengguna = auth()->user();

    /*
     * Satu-satunya daftar menu admin (issue #23).
     * Menambah menu = menambah baris di sini + route dengan nama yang sama.
     * `pola` dipakai menandai menu aktif, termasuk route turunan
     * (admin.pegawai.create, dst.) yang menyusul di issue fitur masing-masing.
     */
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'pola' => 'admin.dashboard'],
        ['label' => 'Pegawai', 'icon' => 'pegawai', 'route' => 'admin.pegawai.index', 'pola' => 'admin.pegawai.*'],
        ['label' => 'Departemen', 'icon' => 'departemen', 'route' => 'admin.departemen.index', 'pola' => 'admin.departemen.*'],
        ['label' => 'Jam Kerja', 'icon' => 'jam', 'route' => 'admin.jam-kerja.index', 'pola' => 'admin.jam-kerja.*'],
        ['label' => 'Cuti', 'icon' => 'cuti', 'route' => 'admin.cuti.index', 'pola' => 'admin.cuti.*'],
        ['label' => 'Saldo Cuti', 'icon' => 'cuti', 'route' => 'admin.saldo-cuti.index', 'pola' => 'admin.saldo-cuti.*'],
        ['label' => 'Hari Libur', 'icon' => 'cuti', 'route' => 'admin.hari-libur.index', 'pola' => 'admin.hari-libur.*'],
        ['label' => 'Monitoring', 'icon' => 'monitoring', 'route' => 'admin.monitoring.index', 'pola' => 'admin.monitoring.*'],
        ['label' => 'Pengajuan', 'icon' => 'pengajuan', 'route' => 'admin.pengajuan.index', 'pola' => 'admin.pengajuan.*'],
        ['label' => 'Laporan', 'icon' => 'laporan', 'route' => 'admin.laporan.index', 'pola' => 'admin.laporan.*'],
        ['label' => 'Rekap', 'icon' => 'laporan', 'route' => 'admin.rekap.index', 'pola' => 'admin.rekap.*'],
        ['label' => 'TUKIN', 'icon' => 'tukin', 'route' => 'admin.tukin.index', 'pola' => 'admin.tukin.*'],
        ['label' => 'Konfigurasi', 'icon' => 'konfigurasi', 'route' => 'admin.konfigurasi.index', 'pola' => 'admin.konfigurasi.*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
        <div class="flex h-16 items-center gap-3 px-4 md:px-6">
            <button
                id="toggle-sidebar"
                type="button"
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Buka menu navigasi"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 md:hidden"
            >
                <x-icon name="menu" />
            </button>

            {{--
                Teks "Esensi" dihapus dari header atas permintaan QA (25 Nov 2026).
                Karena gambar logo bersifat dekoratif (alt=""), tautan jadi tanpa
                nama aksesibel — aria-label ini yang menggantikannya.
            --}}
            <a
                href="{{ route('admin.dashboard') }}"
                aria-label="Beranda admin"
                class="flex items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >
                <img src="{{ asset('images/logo-esensi.jpg') }}" alt="" class="h-8 w-auto object-contain">
            </a>

            <div class="ml-auto flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-medium text-gray-900">{{ $pengguna?->nama }}</p>
                    <p class="text-xs text-gray-600">{{ $pengguna?->role === 'admin' ? 'Administrator' : 'Pegawai' }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm">
                        <x-icon name="logout" size="sm" />
                        <span class="hidden sm:inline">Keluar</span>
                    </x-button>
                </form>
            </div>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-4rem)] flex-col md:flex-row">
        <nav
            id="sidebar"
            aria-label="Menu admin"
            class="hidden w-full shrink-0 border-b border-gray-200 bg-white p-3 md:block md:w-64 md:border-r md:border-b-0 md:p-4"
        >
            <ul class="flex flex-col gap-1">
                @foreach ($menu as $item)
                    @php($aktif = request()->routeIs($item['pola']))
                    <li>
                        <a href="{{ route($item['route']) }}"@if ($aktif) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 {{ $aktif ? 'bg-brand-50 text-brand-800' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                            <x-icon :name="$item['icon']" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <main class="flex-1 p-4 md:p-6">
            <x-flash />

            {{ $slot }}
        </main>
    </div>

    <footer class="border-t border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 md:px-6">
        {{ config('app.name') }} · Sistem kehadiran GPS
    </footer>

    <script>
        (() => {
            const tombol = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');

            tombol.addEventListener('click', () => {
                const sedangTerbuka = !sidebar.classList.contains('hidden');

                sidebar.classList.toggle('hidden', sedangTerbuka);
                tombol.setAttribute('aria-expanded', String(!sedangTerbuka));
                tombol.setAttribute('aria-label', sedangTerbuka ? 'Buka menu navigasi' : 'Tutup menu navigasi');
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
