<x-layouts.admin title="Monitoring">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Monitoring</h1>
            <p class="mt-1 text-sm text-gray-600">Kehadiran hari ini beserta titik lokasi absen di peta.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #41</strong> (monitoring kehadiran hari ini +
                peta koordinat Leaflet). Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/monitoring.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
