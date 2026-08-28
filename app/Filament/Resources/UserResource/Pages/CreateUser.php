<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record; 
        
        $data = $this->form->getRawState();

        if (isset($data['filial_id']) && isset($data['perfil_acesso'])) {
            
            $user->filiais()->attach($data['filial_id'], [
                'perfil_acesso' => $data['perfil_acesso']
            ]);

            $user->assignRole($data['perfil_acesso']);
        }
    }
}