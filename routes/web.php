<?php

use Dengrweb\DAuth\Http\Controllers\DAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('dauth.middleware', ['web']))
    ->prefix(config('dauth.route_prefix', 'auth/dauth'))
    ->name('dauth.')
    ->group(function () {
        Route::get('redirect', [DAuthController::class, 'redirect'])->middleware('throttle:30,1')->name('redirect');
        Route::get('callback', [DAuthController::class, 'callback'])->middleware('throttle:30,1')->name('callback');
        Route::post('logout', [DAuthController::class, 'logout'])->name('logout');
    });
