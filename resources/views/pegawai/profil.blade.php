<x-layouts.pegawai title="Profil">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Profil</h1>
            <p class="mt-1 text-sm text-gray-600">Data akun yang dipakai untuk masuk ke aplikasi.</p>
        </div>

        <x-card title="Akun">
            <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Nama</dt>
                    <dd class="text-right font-medium text-gray-900">{{ auth()->user()?->nama }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Email</dt>
                    <dd class="text-right font-medium text-gray-900">{{ auth()->user()?->email }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-gray-600">Peran</dt>
                    <dd>
                        <x-badge variant="brand">
                            {{ auth()->user()?->role === 'admin' ? 'Administrator' : 'Pegawai' }}
                        </x-badge>
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Keamanan">
            <div class="flex flex-col gap-3">
                <p class="text-sm text-gray-600">
                    Ganti password Anda secara berkala. Lupa password? Hubungi administrator —
                    reset dilakukan admin, tidak lewat email.
                </p>
                <a
                    href="{{ route('password.edit') }}"
                    class="inline-flex items-center gap-1 self-start rounded-md text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                >
                    Ganti password
                </a>
            </div>
        </x-card>
    </div>
</x-layouts.pegawai>
