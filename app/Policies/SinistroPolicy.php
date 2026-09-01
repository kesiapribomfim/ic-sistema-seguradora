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
        //Acesso geral para suporte
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        
        return $user->hasAnyRole([
            'Administrador Geral',
            'Gestor de Filial',
            'Analista de Sinistros',
            'Corretor',
            'Cliente',
            'Financeiro'
        ]);
    }

    public function view(User $user, Sinistro $sinistro): bool
    {
        if ($user->hasRole('Corretor')) {
            return $sinistro->apolice->user_id === $user->id;
        }

        if ($user->hasRole('Cliente')) {
            return $sinistro->apolice->segurado->user_id === $user->id;
        }

        if ($user->hasAnyRole(['Gestor de Filial', 'Analista de Sinistros', 'Financeiro'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            return in_array($sinistro->apolice->filial_id, $filiaisIds);
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Clientes e Corretores abrem sinistros.
        return $user->hasAnyRole(['Corretor', 'Cliente']);
    }

    public function update(User $user, Sinistro $sinistro): bool
    {
        $statusFinalizados = ['Negado', 'Encerrado'];
        if (in_array($sinistro->status, $statusFinalizados)) {
            return false;
        }

        if ($user->hasRole('Corretor')) {
            return $sinistro->apolice->user_id === $user->id;
        }

        if ($user->hasRole('Cliente')) {
            return $sinistro->apolice->segurado->user_id === $user->id;
        }

        if ($user->hasAnyRole(['Analista de Sinistros', 'Gestor de Filial', 'Financeiro'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            return in_array($sinistro->apolice->filial_id, $filiaisIds);
        }

        return false;
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