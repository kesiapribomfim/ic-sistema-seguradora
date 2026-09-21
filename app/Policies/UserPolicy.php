<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Acesso geral para suporte
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
        ]);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        // Admin Geral pode ver qualquer um
        if ($user->hasRole('Administrador Geral')) {
            return true;
        }

        if ($user->hasRole('Gestor de Filial')) {
            $filiaisGestorIds = $user->filiais()
                ->wherePivot('perfil_acesso', 'Gestor de Filial')
                ->pluck('filiais.id')
                ->toArray();

            $filiaisAlvoIds = $model->filiais()->pluck('filiais.id')->toArray();

            return ! empty(array_intersect($filiaisGestorIds, $filiaisAlvoIds));
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['Gestor de Filial', 'Administrador Geral'])) {
            return true;
        }

        return $user->can('create_user');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->hasAnyRole(['Gestor de Filial', 'Administrador Geral'])) {

            $filiaisGestorIds = $user->filiais()
                ->wherePivot('perfil_acesso', 'Gestor de Filial')
                ->pluck('filiais.id')
                ->toArray();

            $filiaisAlvoIds = $model->filiais()->pluck('filiais.id')->toArray();

            return ! empty(array_intersect($filiaisGestorIds, $filiaisAlvoIds));
        }

        return $user->can('update_user');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_user');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_user');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force_delete_user');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_user');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('restore_user');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_user');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function replicate(User $user, User $model): bool
    {
        return $user->can('replicate_user');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_user');
    }
}
