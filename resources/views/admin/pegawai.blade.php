<x-layouts.admin title="Pegawai">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pegawai</h1>
            <p class="mt-1 text-sm text-gray-600">Daftar pegawai, akun, dan penempatannya di departemen.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #28</strong> (CRUD Pegawai + upload foto +
                generate password awal). Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/pegawai.blade.php</code>
                — layout, menu, dan komponennya sudah dipakai bersama.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
