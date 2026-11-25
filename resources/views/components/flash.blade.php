@php
    /*
     * Pesan sesi yang boleh tampil di halaman — dipakai bersama oleh
     * x-layouts.admin dan x-layouts.pegawai, jadi logikanya tidak duplikat.
     * Variannya mengikuti x-alert.
     */
    $pesanSesi = ['success' => 'success', 'warning' => 'warning', 'error' => 'danger'];
@endphp

@foreach ($pesanSesi as $kunci => $variant)
    @if (session($kunci))
        <x-alert :variant="$variant" class="mb-4">{{ session($kunci) }}</x-alert>
    @endif
@endforeach
