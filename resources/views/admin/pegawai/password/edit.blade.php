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

            <x-card>
                <form method="POST" action="{{ route('admin.pegawai.password.update', $pegawai) }}" class="flex flex-col gap-4">
                    @csrf

                    <x-alert variant="info">
                        Admin yang menetapkan password baru. Password tidak akan ditampilkan atau dikirimkan oleh sistem.
                    </x-alert>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password baru</label>
                        <x-input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            :invalid="$errors->has('password')"
                            class="mt-1"
                        />
                        @error('password')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi password baru</label>
                        <x-input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1"
                        />
                    </div>

                    <x-button type="submit">Reset password</x-button>
                </form>
            </x-card>
        </div>
    </main>
</body>
</html>
