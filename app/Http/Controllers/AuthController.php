<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $this->credentialsFor(
            $request->validated('identitas'),
            $request->validated('password'),
        );

        if ($credentials === null || ! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Percobaan login gagal.', [
                'identitas' => $request->validated('identitas'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'identitas' => 'Email/NIP atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (config('hashing.rehash_on_login', true) && Hash::needsRehash($user->password)) {
            $user->forceFill([
                'password' => Hash::make($request->validated('password')),
            ])->save();

            Log::info('Password di-rehash ke Argon2id.', ['user_id' => $user->id]);
        }

        Log::info('Login berhasil.', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect($this->redirectPathForRole($user->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @return array{email: string, password: string}|array{id: int, password: string}|null
     */
    private function credentialsFor(string $identifier, string $password): ?array
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $identifier, 'password' => $password];
        }

        $userId = Pegawai::query()->where('nip', $identifier)->value('user_id');

        return $userId === null
            ? null
            : ['id' => $userId, 'password' => $password];
    }

    private function redirectPathForRole(string $role): string
    {
        return $role === 'admin' ? '/admin/dashboard' : '/dashboard';
    }
}
