<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Rollover tabungan Cuti Tahunan (issue #37). 1 Januari pukul 00.05 waktu
 * kantor; command idempotent dan juga dipanggil lazily dari
 * HitungSisaCutiAction bila cron ini tidak jalan.
 */
Schedule::command('cuti:rollover-tahunan')
    ->yearlyOn(1, 1, '00:05')
    ->timezone((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
