<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;
use App\Http\Controllers\ApolicePdfController;
use App\Livewire\SolicitarCotacao;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/cotacao/{cotacao:uuid}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware(['signed', 'throttle:5,1']);

Route::get('/apolices/{apolice}/pdf', ApolicePdfController::class)
    ->middleware(['auth', 'signed', 'throttle:30,1'])
    ->name('apolices.pdf');

Route::get('/cotacao', SolicitarCotacao::class)
    ->middleware('throttle:5,1');