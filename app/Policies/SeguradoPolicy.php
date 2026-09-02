<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Segurado;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeguradoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
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
            'Corretor'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Segurado $segurado): bool
    {
        if ($user->hasRole('Corretor')) {
            return $segurado->corretor_id === $user->id;
        }

        if ($user->hasAnyRole(['Gestor de Filial', 'Analista de Sinistros'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            
            $hasApolice = $segurado->apolices()->whereIn('filial_id', $filiaisIds)->exists();
            $hasCotacao = $segurado->cotacoes()->whereIn('filial_id', $filiaisIds)->exists();
            
            $corretorFiliais = $segurado->corretor ? $segurado->corretor->filiais()->pluck('filiais.id')->toArray() : [];
            $hasCorretor = !empty(array_intersect($filiaisIds, $corretorFiliais));

            $userFiliais = $segurado->user ? $segurado->user->filiais()->pluck('filiais.id')->toArray() : [];
            $hasUser = !empty(array_intersect($filiaisIds, $userFiliais));

            return $hasApolice || $hasCotacao || $hasCorretor || $hasUser;
        }

        return $user->can('view_segurado');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['Gestor de Filial','Corretor'])){
            return true;
        }
        return $user->can('create_segurado');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Segurado $segurado): bool
    {
        if ($user->hasAnyRole(['Gestor de Filial', 'Corretor'])) {
            return $this->view($user, $segurado);
        }

        return $user->can('update_segurado');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Segurado $segurado): bool
    {
        return $user->can('delete_segurado');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_segurado');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Segurado $segurado): bool
    {
        return $user->can('force_delete_segurado');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_segurado');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Segurado $segurado): bool
    {
        return $user->can('restore_segurado');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_segurado');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Segurado $segurado): bool
    {
        return $user->can('replicate_segurado');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_segurado');
    }
}
