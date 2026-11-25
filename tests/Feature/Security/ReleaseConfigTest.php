<?php

use Illuminate\Support\Facades\File;

it('documents production security flags in env.example', function () {
    $env = File::get(base_path('.env.example'));

    expect($env)->toContain('APP_ENV=local')
        ->and($env)->toContain('APP_DEBUG=true')
        ->and($env)->toContain('APP_ENV=production')
        ->and($env)->toContain('APP_DEBUG=false')
        ->and($env)->toContain('SESSION_SECURE_COOKIE=false')
        ->and($env)->toContain('Produksi wajib true');
});

it('ships a production release checklist', function () {
    expect(File::exists(base_path('docs/checklist-rilis.md')))->toBeTrue();
});
