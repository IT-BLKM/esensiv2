<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password.edit');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $user->update(['password' => $request->validated('password')]);

        return redirect()->route('password.edit')->with('status', 'Password berhasil diubah.');
    }
}
