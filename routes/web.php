<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cotacao/{cotacao:uuid}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware('throttle: 5,1');