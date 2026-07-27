<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perfis = [
            'Administrador Geral',
            'Gestor de Filial',
            'Subscritor',
            'Corretor',
            'Analista de Sinistros',
            'Financeiro',
            'Cliente',
        ];

        foreach ($perfis as $perfil){
            Role::firstOrCreate(['name' => $perfil]);
        }
    }
}
