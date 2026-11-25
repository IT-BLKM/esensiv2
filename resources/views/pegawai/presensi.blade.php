<x-layouts.pegawai title="Presensi">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Presensi</h1>
            <p class="mt-1 text-sm text-gray-600">Absen masuk dan pulang dengan foto, lokasi GPS, dan verifikasi wajah.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #31</strong> (webcam + GPS + face-api.js, UI saja)
                dan <strong>issue #32</strong> (penyimpanan di server: validasi radius, foto, alur in/out).
                Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/pegawai/presensi.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.pegawai>
