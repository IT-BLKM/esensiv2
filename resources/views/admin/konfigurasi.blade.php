<x-layouts.admin title="Konfigurasi">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Konfigurasi</h1>
            <p class="mt-1 text-sm text-gray-600">Jam kerja per pegawai, titik lokasi kantor, dan radius absen.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #29</strong> (pengaturan jam kerja per pegawai)
                dan <strong>issue #30</strong> (info lokasi &amp; radius kantor, hanya-baca). Saat fitur itu
                selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/konfigurasi.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
