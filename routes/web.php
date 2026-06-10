<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


//nova rota criada
Route::get('/home', function () {
    return view('home');
})->middleware(['auth'])->name('home');