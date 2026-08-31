<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        // Todos que têm acesso ao painel interno podem ver o catálogo
        return $user->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial', 'Analista de Sinistros', 'Corretor']);
    }

    public function view(User $user, Produto $produto): bool
    {
        return $user->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial', 'Analista de Sinistros', 'Corretor']);
    }

    public function create(User $user): bool
    {
        // Só a diretoria cria produtos novos
        return $user->hasAnyRole(['super_admin', 'Administrador Geral']);
    }

    public function update(User $user, Produto $produto): bool
    {
        return $user->hasAnyRole(['super_admin', 'Administrador Geral']);
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $user->hasRole('super_admin');
    }
}
