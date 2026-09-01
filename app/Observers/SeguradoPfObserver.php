<?php

namespace App\Observers;

use App\Models\SeguradoPf;
use Illuminate\Support\Facades\Log;

class SeguradoPfObserver
{
    public function saved(SeguradoPf $seguradoPf): void
    {

        $seguradoAtualizado = $seguradoPf->segurado()->first();

        if ($seguradoAtualizado && $seguradoAtualizado->user_id) {
            $user = \App\Models\User::find($seguradoAtualizado->user_id);
            
            if ($user) {
                $user->updateQuietly(['name' => $seguradoPf->nome]);
            }
        }
    }

    /**
     * Handle the SeguradoPf "created" event.
     */
    public function created(SeguradoPf $seguradoPf): void
    {
        //
    }

    /**
     * Handle the SeguradoPf "updated" event.
     */
    public function updated(SeguradoPf $seguradoPf): void
    {
        //
    }

    /**
     * Handle the SeguradoPf "deleted" event.
     */
    public function deleted(SeguradoPf $seguradoPf): void
    {
        //
    }

    /**
     * Handle the SeguradoPf "restored" event.
     */
    public function restored(SeguradoPf $seguradoPf): void
    {
        //
    }

    /**
     * Handle the SeguradoPf "force deleted" event.
     */
    public function forceDeleted(SeguradoPf $seguradoPf): void
    {
        //
    }
}
