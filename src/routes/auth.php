<?php

use Illuminate\Support\Facades\Route;
use Saola\Core\Http\Controllers\AuthController;

Route::middleware('web')->prefix(config('sao.auth_api.prefix', 'auth'))->name('saola.auth.')->group(function () {
    Route::get('session', [AuthController::class, 'session'])->name('session');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:saola.auth.login')->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('{provider}/redirect', [AuthController::class, 'redirect'])->middleware('throttle:20,1')->name('redirect');
    Route::get('{provider}/callback', [AuthController::class, 'callback'])->middleware('throttle:20,1')->name('callback');
});
