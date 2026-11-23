<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            throw ValidationException::withMessages([
                'identitas' => 'Email/NIP atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

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
