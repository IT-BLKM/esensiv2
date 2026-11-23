<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <p class="text-sm font-medium text-brand-700">{{ config('app.name') }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900">Masuk</h1>
                <p class="mt-1 text-sm text-gray-600">Gunakan email atau NIP yang terdaftar.</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="identitas" class="block text-sm font-medium text-gray-700">Email atau NIP</label>
                        <x-input
                            id="identitas"
                            name="identitas"
                            type="text"
                            value="{{ old('identitas') }}"
                            placeholder="nama@email.com atau NIP 18 digit"
                            autocomplete="username"
                            :invalid="$errors->has('identitas')"
                            class="mt-1"
                        />
                        @error('identitas')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <x-input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            :invalid="$errors->has('password')"
                            class="mt-1"
                        />
                        @error('password')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-4 w-4 rounded border-gray-300"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        Ingat saya
                    </label>

                    <x-button type="submit" class="w-full">Masuk</x-button>
                </form>
            </x-card>

            <p class="mt-4 text-center text-xs text-gray-600">
                Lupa password? Hubungi administrator.
            </p>
        </div>
    </main>
</body>
</html>
