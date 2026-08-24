<?php

namespace App\Http\Controllers;

use App\Models\Apolice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApolicePdfController extends Controller
{
    public function __invoke(Request $request, Apolice $apolice)
    {
        Gate::authorize('view', $apolice);

        $apolice->loadMissing([
            'segurado.seguradoPf',
            'segurado.seguradoPj',
            'cotacao.produto',
            'pagamentos',
        ]);

        return Pdf::loadView('pdf.apolice', compact('apolice'))
            ->download("apolice-{$apolice->numero_apolice}.pdf");
    }
}
