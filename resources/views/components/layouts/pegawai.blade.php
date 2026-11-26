@props([
    'title' => null,
])

@php
    $pengguna = auth()->user();

    /*
     * Satu-satunya daftar bottom navigation pegawai (issue #24).
     * Menambah menu = menambah baris di sini + route dengan nama tersebut.
     * `pola` dipakai menandai menu aktif, termasuk route turunan
     * (pengajuan.create, dst.) yang menyusul di issue fitur masing-masing.
     */
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'pola' => 'dashboard'],
        ['label' => 'Presensi', 'icon' => 'presensi', 'route' => 'presensi.index', 'pola' => 'presensi.*'],
        ['label' => 'Histori', 'icon' => 'histori', 'route' => 'histori.index', 'pola' => 'histori.*'],
        ['label' => 'TUKIN', 'icon' => 'tukin', 'route' => 'tukin.index', 'pola' => 'tukin.*'],
        ['label' => 'Pengajuan', 'icon' => 'pengajuan', 'route' => 'pengajuan.index', 'pola' => 'pengajuan.*'],
        ['label' => 'Profil', 'icon' => 'user', 'route' => 'profil', 'pola' => 'profil'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-14 w-full max-w-3xl items-center gap-3 px-4">
            <img src="{{ asset('images/logo-esensi.jpg') }}" alt="" class="h-8 w-auto object-contain">

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-900">{{ $pengguna?->nama }}</p>
                <p class="truncate text-xs text-gray-600">{{ $pengguna?->role === 'admin' ? 'Administrator' : 'Pegawai' }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button type="submit" variant="ghost" size="sm" aria-label="Keluar">
                    <x-icon name="logout" size="sm" />
                </x-button>
            </form>
        </div>
    </header>

    {{-- Konten dapat di atas bottom nav: pb-24 memberi ruang untuk nav tetap. --}}
    <main class="mx-auto w-full max-w-3xl px-4 pt-4 pb-24">
        <x-flash />

        {{ $slot }}
    </main>

    <nav
        id="bottom-nav"
        aria-label="Menu pegawai"
        class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white"
    >
        <ul class="mx-auto flex w-full max-w-3xl">
            @foreach ($menu as $item)
                @php($aktif = request()->routeIs($item['pola']))
                <li class="flex-1">
                    <a href="{{ route($item['route']) }}"@if ($aktif) aria-current="page" @endif class="relative flex flex-col items-center gap-0.5 px-1 pt-2.5 pb-2 text-[11px] font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 {{ $aktif ? 'text-brand-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        @if ($aktif)
                            <span class="absolute inset-x-5 top-0 h-0.5 rounded-full bg-brand-600"></span>
                        @endif
                        <x-icon :name="$item['icon']" />
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @stack('scripts')
</body>
</html>
