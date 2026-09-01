<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Filial;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilialPolicy
{
    use HandlesAuthorization;

    /**
     * Validação do Princípio do Menor Privilégio para Filiais.
     */
    private function verificaEscopo(User $user, Filial $filial): bool
    {
        // Admin Geral manda em tudo
        if ($user->hasRole('Administrador Geral')) {
            return true;
        }

        // Gestor de Filial só tem acesso se estiver vinculado a ESTA filial específica
        if ($user->hasRole('Gestor de Filial')) {
            
            $filiaisComoGestorIds = $user->filiais()
                ->wherePivot('perfil_acesso', 'Gestor de Filial') 
                ->pluck('filiais.id')
                ->toArray();
            
            return in_array($filial->id, $filiaisComoGestorIds);
        }

        return false;
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrador Geral', 'Gestor de Filial']);
    }

    public function view(User $user, Filial $filial): bool
    {
        return $this->verificaEscopo($user, $filial);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador Geral');
    }

    public function update(User $user, Filial $filial): bool
    {
        return $this->verificaEscopo($user, $filial);
    }

    public function delete(User $user, Filial $filial): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Filial $filial): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Filial $filial): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Filial $filial): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}