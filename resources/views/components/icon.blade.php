@props([
    'name' => 'dashboard',
    'size' => 'md',
])

@php
    /*
     * Ikon stroke milik sendiri (bawaan proyek, tanpa dependensi ikon).
     * Daftar nama yang didukung dicatat di docs/konvensi-ui.md §4 dan
     * dipamerkan utuh di halaman /dev/komponen — ikon tidak ditulis tempel
     * per halaman.
     *
     * $path berasal dari daftar putih di atas, bukan dari input pengguna,
     * sehingga aman ditempel apa adanya.
     */
    $path = match ($name) {
        'dashboard' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'pegawai' => '<circle cx="9" cy="8" r="3.25"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><path d="M16.25 5.2a3.25 3.25 0 0 1 0 6.1"/><path d="M17.5 14.6a5.5 5.5 0 0 1 3 4.9"/>',
        'departemen' => '<path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16"/><path d="M15 10h4a1 1 0 0 1 1 1v10"/><path d="M2 21h20"/><path d="M7.5 8h3M7.5 12h3M7.5 16h3"/>',
        'cuti' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17"/><path d="M8 3v4M16 3v4"/>',
        'monitoring' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'pengajuan' => '<rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M8.5 11h7M8.5 15h5"/>',
        'laporan' => '<path d="M4 4v16h16"/><path d="M8 16v-4M12.5 16V8M17 16v-6"/>',
        'konfigurasi' => '<path d="M4 7h6M14 7h6M4 17h10M18 17h2"/><circle cx="12" cy="7" r="2"/><circle cx="16" cy="17" r="2"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/>',
        default => '',
    };

    $sizeClasses = match ($size) {
        'sm' => 'h-4 w-4',
        'lg' => 'h-6 w-6',
        default => 'h-5 w-5',
    };
@endphp

<svg
    {{ $attributes->class(['shrink-0', $sizeClasses]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $path !!}</svg>
