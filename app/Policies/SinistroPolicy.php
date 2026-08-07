<?php

namespace App\Policies;

use App\Models\Sinistro;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SinistroPolicy
{
    /**
     * O método before é interceptado antes de qualquer outra verificação.
     * Garante o acesso irrestrito do Administrador Geral exigido na documentação.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador Geral')) {
            return true;
        }

        return null; // Retorna null para continuar as validações específicas abaixo
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Gestor de Filial', 'Analista de Sinistros', 'Corretor', 'Cliente']);
    }

    public function view(User $user, Sinistro $sinistro): bool
    {
        return $user->hasAnyRole(['Gestor de Filial', 'Analista de Sinistros', 'Corretor', 'Cliente']);
    }

    public function create(User $user): bool
    {
        // Clientes e Corretores abrem sinistros. Analistas geralmente não abrem, apenas avaliam.
        return $user->hasAnyRole(['Corretor', 'Cliente']);
    }

    public function update(User $user, Sinistro $sinistro): bool
    {
        // Apenas Analistas movimentam o sinistro no backoffice
        return $user->hasRole('Analista de Sinistros');
    }

    public function delete(User $user, Sinistro $sinistro): bool
    {
        // Como o documento exige imutabilidade (PoLP e auditoria), ninguém apaga sinistros.
        return false; 
    }

    public function restore(User $user, Sinistro $sinistro): bool
    {
        return false;
    }

    public function forceDelete(User $user, Sinistro $sinistro): bool
    {
        return false;
    }
}