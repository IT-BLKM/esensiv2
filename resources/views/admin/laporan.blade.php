<x-layouts.admin title="Laporan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Laporan</h1>
            <p class="mt-1 text-sm text-gray-600">Rekap kehadiran bulanan per pegawai, siap diekspor.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #42</strong> (laporan bulanan per pegawai +
                filter), dengan rekap keterlambatan dari <strong>issue #44</strong> dan ekspor dari
                <strong>issue #43</strong>. Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/laporan.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
