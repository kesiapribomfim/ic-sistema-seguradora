<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Apolice;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cotacao/{cotacao:uuid}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware('throttle: 5,1');

Route::get('/teste-pdf/{apolice}', function (Apolice $apolice) {
    $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $apolice]);
    
    // strem(): Directly display in browser for viewing/printing
    return $pdf->stream('apolice.pdf');
});