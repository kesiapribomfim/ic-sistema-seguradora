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

        $user = \App\Models\User::create([
            'name' => 'Cliente',
            'email' => $segurado->email,
            'password' => bcrypt(\Illuminate\Support\Str::password(32)),
        ]);

        $user->assignRole('Cliente');

        $corretor = \App\Models\User::find($segurado->corretor_id);

        if ($corretor) {
            $filialCorretor = $corretor->filiais()->first(); 
            
            if ($filialCorretor) {
                $user->filiais()->attach($filialCorretor->id, [
                    'perfil_acesso' => 'Cliente'
                ]);
                Log::info("Usuário Cliente ID {$user->id} vinculado à Filial ID {$filialCorretor->id}");
            }
        }

        $segurado->updateQuietly(['user_id' => $user->id]);

        Log::info("User criado e associado ao segurado#{$segurado->id}");
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
