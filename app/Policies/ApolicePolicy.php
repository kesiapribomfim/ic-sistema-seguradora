<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Apolice;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApolicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */

    public function before(User $user, string $ability): ?bool
    {
        //Admin Geral acesso
        if ($user->hasRole('Administrador Geral') || $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Gestor de Filial',
            'Analista de Sinistros',
            'Corretor',
            'Cliente', 
            'Financeiro'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Apolice $apolice): bool
    {
        if ($user->hasRole('Corretor')) {
            return $apolice->user_id === $user->id;
        }

        if ($user->hasRole('Cliente')) {
            return $apolice->segurado?->user_id === $user->id;
        }

        if ($user -> hasAnyRole([
            'Gestor de Filial',
            'Analista de Sinistros',
            'Financeiro'
        ])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            return in_array($apolice->filial_id, $filiaisIds);
        }
        return $user->can('view_apolice');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_apolice');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Apolice $apolice): bool
    {
        return false;
    
    }
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Apolice $apolice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_apolice');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Apolice $apolice): bool
    {
        return $user->can('force_delete_apolice');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_apolice');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Apolice $apolice): bool
    {
        return $user->can('restore_apolice');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_apolice');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Apolice $apolice): bool
    {
        return $user->can('replicate_apolice');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_apolice');
    }
}
