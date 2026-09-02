<?php

namespace App\Jobs;

use App\Models\Segurado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalcularScoreRiscoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $seguradoId;

    public function __construct($seguradoId)
    {
        $this->seguradoId = $seguradoId;
    }

    public function handle(): void
    {
        $segurado = Segurado::find($this->seguradoId);

        if ($segurado && $segurado->score > 0) {
            $novoScore = max(0, $segurado->score - 10);

            $segurado->update([
                'score' => $novoScore
            ]);
        }
    }
}
