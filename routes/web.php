<?php

use App\Http\Controllers\Admin\PegawaiPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => 'Dashboard pegawai')
        ->middleware('role:pegawai')
        ->name('dashboard');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', fn () => 'Dashboard admin')->name('dashboard');

        Route::get('/pegawai/{pegawai}/password/edit', [PegawaiPasswordController::class, 'edit'])
            ->name('pegawai.password.edit');
        Route::post('/pegawai/{pegawai}/password', [PegawaiPasswordController::class, 'update'])
            ->name('pegawai.password.update');
    });
});

/*
 * Satu guard `web`, satu halaman login. Route admin/pegawai menyusul di #19;
 * throttle login menyusul di #20 (docs/panduan-kolaborasi.md §13).
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

/*
 * Halaman referensi design system (docs/konvensi-ui.md §6).
 * Hanya untuk lingkungan lokal/testing — tidak boleh terlihat di produksi.
 */
if (! app()->isProduction()) {
    Route::get('/dev/komponen', fn () => view('dev.komponen'))->name('dev.komponen');
}
