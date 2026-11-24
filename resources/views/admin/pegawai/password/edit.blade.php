<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password {{ $pegawai->nama }} — Esensi</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold text-gray-900">Reset Password Pegawai</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $pegawai->nama }} — NIP {{ $pegawai->nip }}</p>
            </div>

            @if (session('status'))
                <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @if (session('passwordSekali'))
                <x-alert variant="warning" class="mb-4" title="Simpan password ini sekarang">
                    <code class="rounded bg-white px-2 py-1 font-mono text-sm">{{ session('passwordSekali') }}</code>
                    <p class="mt-1">Password acak ini hanya ditampilkan sekali. Sampaikan ke pegawai sekarang; sistem tidak menyimpannya dalam bentuk plaintext.</p>
                </x-alert>
            @endif

            <x-card>
                <form method="POST" action="{{ route('admin.pegawai.password.update', $pegawai) }}" class="flex flex-col gap-4">
                    @csrf

                    <x-alert variant="info">
                        Sistem akan membuat password acak yang kuat dan mengganti password lama. Tidak perlu mengetik password baru.
                    </x-alert>

                    <x-button type="submit">Buat password baru</x-button>
                </form>
            </x-card>

            <p class="mt-4 text-center">
                <a
                    href="{{ route('admin.pegawai.index') }}"
                    class="text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                >Kembali ke daftar pegawai</a>
            </p>
        </div>
    </main>
</body>
</html>
