<?php

use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('factory creates a fulfilled replacement linked to presensi', function () {
    $ganti = PenggantianKeterlambatan::factory()->create();

    expect($ganti->presensi)->toBeInstanceOf(Presensi::class)
        ->and($ganti->bisa_diganti)->toBeTrue()
        ->and($ganti->terpenuhi)->toBeTrue()
        ->and($ganti->menit_terlambat)->toBe(20);
});

test('factory states cover unfulfilled and not replaceable rows', function () {
    $gagal = PenggantianKeterlambatan::factory()->tidakTerpenuhi()->create();
    $tidakBisa = PenggantianKeterlambatan::factory()->tidakBisaDiganti()->create();

    expect($gagal->terpenuhi)->toBeFalse()
        ->and($gagal->menit_diganti)->toBe(0)
        ->and($tidakBisa->bisa_diganti)->toBeFalse()
        ->and($tidakBisa->target_pulang)->toBeNull();
});
