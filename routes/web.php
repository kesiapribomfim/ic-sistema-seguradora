<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CheckoutCotacao;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Apolice;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cotacao/{cotacao:uuid}/checkout', CheckoutCotacao::class)
    ->name('checkout.cotacao')
    ->middleware(['signed', 'throttle:5,1']);

// --- ROTA DE TESTE PDF ---
Route::get('/teste-pdf/{apolice}', function (Apolice $apolice) {
    $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $apolice]);
    
    // stream(): Directly display in browser for viewing/printing
    return $pdf->stream('apolice.pdf');
}); // <--- AQUI! VOCÊ TINHA ESQUECIDO DE FECHAR ESTA ROTA AQUI!


// --- ROTA DE RESET DE SENHA ---
Route::get('/reset-password/{token}', function (Request $request, $token) {
    return "Bem-vindo ao Portal do Cliente! Seu Token de redefinição é: {$token} e seu email é: {$request->query('email')}";
})->name('password.reset');