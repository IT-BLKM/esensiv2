<x-layouts.pegawai title="Histori">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Histori presensi</h1>
            <p class="mt-1 text-sm text-gray-600">Catatan kehadiran bulanan Anda, bisa difilter per bulan.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #33</strong> (histori presensi pegawai:
                filter bulan + query binding). Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/pegawai/histori.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.pegawai>
