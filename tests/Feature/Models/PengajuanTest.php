<?php

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a pengajuan with its approval audit columns', function () {
    $approver = User::factory()->create();
    $pengajuan = Pengajuan::factory()->create([
        'status' => 'approved',
        'disetujui_oleh' => $approver->id,
        'disetujui_pada' => now(),
        'catatan' => 'Disetujui admin',
    ]);

    $this->assertModelExists($pengajuan);
    expect($pengajuan->status)->toBe('approved')
        ->and($pengajuan->disetujui_oleh)->toBe($approver->id)
        ->and($pengajuan->catatan)->toBe('Disetujui admin');
});

test('database rejects cuti without id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'cuti', 'id_cuti' => null]);
})->throws(QueryException::class);

test('database rejects sakit with id_cuti', function () {
    $pengajuan = Pengajuan::factory()->create();

    Pengajuan::factory()->create([
        'jenis' => 'sakit',
        'id_cuti' => $pengajuan->id_cuti,
    ]);
})->throws(QueryException::class);

test('database rejects dl with id_cuti', function () {
    $pengajuan = Pengajuan::factory()->create();

    Pengajuan::factory()->create([
        'jenis' => 'dl',
        'id_cuti' => $pengajuan->id_cuti,
    ]);
})->throws(QueryException::class);

test('allows sakit and dl without id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'sakit', 'id_cuti' => null]);
    Pengajuan::factory()->create(['jenis' => 'dl', 'id_cuti' => null]);

    $this->assertDatabaseCount('pengajuan', 2);
});

test('factory states cover pending, approved, rejected, sakit, and dl', function () {
    $admin = User::factory()->admin()->create();

    $pending = Pengajuan::factory()->pending()->create();
    $approved = Pengajuan::factory()->approvedBy($admin)->create();
    $rejected = Pengajuan::factory()->rejectedBy($admin, 'Tidak sesuai')->create();
    $sakit = Pengajuan::factory()->sakit()->create();
    $dl = Pengajuan::factory()->dl('setengah')->create();

    expect($pending->status)->toBe('pending')
        ->and($approved->status)->toBe('approved')
        ->and($approved->disetujui_oleh)->toBe($admin->id)
        ->and($rejected->status)->toBe('rejected')
        ->and($rejected->catatan)->toBe('Tidak sesuai')
        ->and($sakit->jenis)->toBe('sakit')
        ->and($sakit->id_cuti)->toBeNull()
        ->and($dl->jenis)->toBe('dl')
        ->and($dl->mode_dl)->toBe('setengah')
        ->and($dl->id_cuti)->toBeNull();
});
