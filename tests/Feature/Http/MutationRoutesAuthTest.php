<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('protects every mutating route with auth and web/CSRF middleware', function () {
    $mutasi = collect(Route::getRoutes())->filter(
        fn ($route): bool => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []
            && $route->getName() !== 'login.store'
            && ! str_starts_with((string) $route->getName(), 'boost.')
            && ! str_starts_with((string) $route->getName(), 'storage.'),
    );

    expect($mutasi)->not->toBeEmpty();

    foreach ($mutasi as $route) {
        $middleware = $route->gatherMiddleware();

        expect($middleware)->toContain('web', 'auth');
    }
});
