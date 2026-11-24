<?php

use App\Http\Controllers\Admin\DepartemenController;
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
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        Route::get('/pegawai/{pegawai}/password/edit', [PegawaiPasswordController::class, 'edit'])
            ->name('pegawai.password.edit');
        Route::post('/pegawai/{pegawai}/password', [PegawaiPasswordController::class, 'update'])
            ->name('pegawai.password.update');

        /*
         * Halaman kosong penutup menu sidebar (issue #23). Nama route-nya sudah
         * bentuk akhir mengikuti konvensi (panduan-kolaborasi.md §13): tiap issue
         * fitur mengganti closure ini dengan Route::resource + controller sendiri,
         * nama route `admin.<menu>.index` tidak berubah sehingga tautan sidebar
         * tidak pernah rusak. Departemen sudah diganti di issue #25.
         */
        Route::get('/pegawai', fn () => view('admin.pegawai'))->name('pegawai.index');
        /*
         * `Str::singular('departemen')` menghasilkan `departeman`; parameter dipaksa
         * tetap `departemen` supaya cocok dengan argumen controller dan policy.
         */
        Route::resource('departemen', DepartemenController::class)
            ->parameters(['departemen' => 'departemen'])
            ->except('show');
        Route::get('/cuti', fn () => view('admin.cuti'))->name('cuti.index');
        Route::get('/monitoring', fn () => view('admin.monitoring'))->name('monitoring.index');
        Route::get('/pengajuan', fn () => view('admin.pengajuan'))->name('pengajuan.index');
        Route::get('/laporan', fn () => view('admin.laporan'))->name('laporan.index');
        Route::get('/konfigurasi', fn () => view('admin.konfigurasi'))->name('konfigurasi.index');
    });
});

/*
 * Satu guard `web`, satu halaman login. Throttle 5 percobaan/menit mengunci
 * route login sesuai docs/panduan-kolaborasi.md §13 aturan 7.
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
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
