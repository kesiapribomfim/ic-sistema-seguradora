<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cotacao/{cotacao}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware('signed');