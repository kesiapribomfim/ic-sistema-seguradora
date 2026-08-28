<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cotacao;
use Illuminate\Auth\Access\HandlesAuthorization;

class CotacaoPolicy
{
    use HandlesAuthorization;

    /**
     * Validação centralizada do Princípio do Menor Privilégio (PoLP).
     * Retorna true apenas se a cotação pertencer ao universo do usuário logado.
     */
    private function verificaEscopo(User $user, Cotacao $cotacao): bool
    {
        // Corretor: Acesso restrito à própria carteira
        if ($user->hasRole('Corretor')) {
            return $cotacao->user_id === $user->id;
        }

        if ($user->hasAnyRole(['Subscritor', 'Gestor de Filial'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            return in_array($cotacao->filial_id, $filiaisIds);
        }

        // Cliente: Acesso restrito às suas próprias cotações - TODO
        if ($user->hasRole('Cliente')) {
            return $cotacao->segurado->user_id === $user->id; 
        }

        return false;
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador Geral') || $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }    

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Gestor de Filial',
            'Subscritor',
            'Corretor',
            'Cliente'
        ]);
    }

    public function view(User $user, Cotacao $cotacao): bool
    {
        if (!$this->verificaEscopo($user, $cotacao)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Corretor')) {
            return true;
        }
        
        return $user->can('create_cotacao');
    }

    public function update(User $user, Cotacao $cotacao): bool
    {
        if (in_array($cotacao->status, ['Aceita', 'Recusada', 'Expirada'])) {
            return false;
        }

        if (!$this->verificaEscopo($user, $cotacao)) {
            return false;
        }

        if ($user->hasRole('Subscritor')) {
            return $cotacao->status === 'Em Subscrição';
        }

        return true;
    }

    public function delete(User $user, Cotacao $cotacao): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Cotacao $cotacao): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Cotacao $cotacao): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Cotacao $cotacao): bool
    {
        // 1. Apenas se o usuário tiver acesso à cotação original
        if (!$this->verificaEscopo($user, $cotacao)) {
            return false;
        }
        
        return $user->can('replicate_cotacao');
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}