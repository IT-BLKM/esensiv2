<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

uses(LazilyRefreshDatabase::class);

function fileTerlacak(): array
{
    $hasil = Process::path(base_path())->run(['git', 'ls-files', '-z']);

    expect($hasil->successful())->toBeTrue();

    return array_values(array_filter(explode("\0", $hasil->output())));
}

it('does not track database dumps, error logs, or a real .env file', function () {
    $terlarang = collect(fileTerlacak())->filter(function (string $file): bool {
        if ($file === '.env.example') {
            return false;
        }

        return str_ends_with($file, '.sql')
            || str_ends_with($file, '.sql.gz')
            || str_ends_with($file, '.dump')
            || str_ends_with($file, 'error_log')
            || $file === '.env'
            || str_starts_with($file, '.env.');
    });

    expect($terlarang->values()->all())->toBe([]);
});

it('does not track private or public attendance files', function () {
    $storage = collect(fileTerlacak())->filter(
        fn (string $file): bool => str_starts_with($file, 'storage/app/private/')
            || str_starts_with($file, 'storage/app/public/')
            || str_starts_with($file, 'public/uploads/'),
    );

    expect($storage->reject(fn (string $file): bool => str_ends_with($file, '.gitignore'))->values()->all())->toBe([]);
});

it('keeps gitignore guards for secrets and data dumps', function () {
    $ignore = File::get(base_path('.gitignore'));

    expect($ignore)->toContain('*.sql')
        ->and($ignore)->toContain('.env')
        ->and($ignore)->toContain('/public/storage')
        ->and($ignore)->toContain('/storage/*.key');
});

it('keeps .env.example free of real keys and passwords', function () {
    $env = File::get(base_path('.env.example'));

    expect($env)->toContain("APP_KEY=\n")
        ->and($env)->toContain('# DB_PASSWORD=')
        ->and($env)->toContain('AWS_SECRET_ACCESS_KEY=')
        ->and($env)->not->toContain('BEGIN PRIVATE KEY')
        ->and($env)->not->toContain('APP_KEY=base64:');
});
