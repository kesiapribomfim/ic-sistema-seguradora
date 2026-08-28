<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;
use App\Http\Controllers\ApolicePdfController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Livewire\SolicitarCotacao;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/cotacao/{cotacao:uuid}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware(['signed', 'throttle:5,1']);

Route::get('/apolices/{apolice}/pdf', ApolicePdfController::class)
    ->middleware(['auth', 'throttle:30,1'])
    ->name('apolices.pdf');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.store');

Route::get('/cotacao', SolicitarCotacao::class)
    ->middleware('throttle:5,1');