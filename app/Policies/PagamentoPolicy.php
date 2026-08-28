<?php

namespace App\Policies;

use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PagamentoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador Geral')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Gestor de Filial', 'Financeiro', 'Cliente']);
    }

    public function view(User $user, Pagamento $pagamento): bool
    {
        return $user->hasAnyRole(['Gestor de Filial', 'Financeiro', 'Cliente']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Financeiro');
    }

    public function update(User $user, Pagamento $pagamento): bool
    {
        return $user->hasRole('Financeiro');
    }

    public function delete(User $user, Pagamento $pagamento): bool
    {
        // Evita fraudes financeiras. Cancelamentos devem ser feitos via status, e não exclusão do banco.
        return false;
    }

    public function restore(User $user, Pagamento $pagamento): bool
    {
        return false;
    }

    public function forceDelete(User $user, Pagamento $pagamento): bool
    {
        return false;
    }
}