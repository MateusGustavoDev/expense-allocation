<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// O Railway termina o HTTPS no proxy e repassa a requisição em HTTP com X-Forwarded-Proto

it('generates https urls behind the platform proxy', function () {
    Route::get('/_test/url', fn () => url('/build/app.css'));

    $this->get('/_test/url', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'rateio.up.railway.app'])
        ->assertSeeText('https://rateio.up.railway.app/build/app.css');
});

it('keeps plain http urls when there is no proxy', function () {
    Route::get('/_test/url', fn () => url('/build/app.css'));

    $this->get('/_test/url')
        ->assertSeeText('http://')
        ->assertDontSeeText('https://');
});
