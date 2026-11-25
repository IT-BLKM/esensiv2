<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password — Esensi</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold text-gray-900">Ganti Password</h1>
                <p class="mt-1 text-sm text-gray-600">Perbarui password akun Anda.</p>
                <a
                    href="{{ auth()->user()?->role === 'admin' ? route('admin.dashboard') : route('dashboard') }}"
                    class="mt-3 inline-flex items-center text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                >Kembali ke beranda</a>
            </div>

            @if (session('status'))
                <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            <x-card>
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Password lama</label>
                        <x-input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            :invalid="$errors->has('current_password')"
                            class="mt-1"
                        />
                        @error('current_password')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

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

                    <x-button type="submit">Simpan password</x-button>
                </form>
            </x-card>
        </div>
    </main>
</body>
</html>
