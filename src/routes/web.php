<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/ping', function () {
    return 'Pong';
});

// We need this route to generate email URL to NodeJS frontend Application
Route::domain('web.' . env('APP_URL'))->group(function () {
    Route::get('/new-credit-card/{slug}', function () {
        return '';
    })->name('web.customer-add-cc');

    Route::get('/make-payment/{slug}', function () {
        return '';
    })->name('web.customer-make-payment');

    Route::get('/worldpay/transaction-setup-callback', function () {
        return '';
    })->name('web.worldpay-transaction-setup-callback');
});
