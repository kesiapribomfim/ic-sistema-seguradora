<?php

namespace App\Observers;

use App\Models\Segurado;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;



class SeguradoObserver
{
    /**
     * Handle the Segurado "created" event.
     */
    public function created(Segurado $segurado): void
    {
        Log::info("Iniciando criação de usuário para o segurado ID: {$segurado->id}");

        if ($segurado->user_id) {
            return;
        }

        $user = User::create([
            'name' => 'Cliente',
            'email' => $segurado->email,
            'password' => bcrypt(Str::password(32)),

        ]);

        $user->assignRole('Cliente');

        $segurado->updateQuietly(['user_id' => $user->id]);

        Log::info("User criado associado ao segurado#{$segurado->id}");
    }

    /**
     * Handle the Segurado "updated" event.
     */
    public function updated(Segurado $segurado): void
    {
        //
    }

    /**
     * Handle the Segurado "deleted" event.
     */
    public function deleted(Segurado $segurado): void
    {
        //
    }

    /**
     * Handle the Segurado "restored" event.
     */
    public function restored(Segurado $segurado): void
    {
        //
    }

    /**
     * Handle the Segurado "force deleted" event.
     */
    public function forceDeleted(Segurado $segurado): void
    {
        //
    }
}
