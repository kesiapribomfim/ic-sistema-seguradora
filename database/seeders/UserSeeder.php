<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Filial;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        //criar o administrador geral
        $admin = User::factory()->create([
            'name' => 'Admin Geral',
            'email' => 'admin_test@exemplo.com',
            'password' => 'password',
            'status' => true,
        ]);

        $admin->assignRole('super_admin');
        
        $corretor = User::factory()->create([
            'name' => 'Elean',
            'email' => 'elean@corretor.com',
            'password' => 'password',
            'status' => true,
        ]);

        $filiais = Filial::all();

        $corretor->filiais()->attach($filiais->random()->id, [
            'perfil_acesso' => 'Corretor'
        ]);
        $corretor->assignRole('Corretor');

        $perfis = [
            'Gestor de Filial', 
            'Subscritor',
            'Corretor',
            'Analista de Sinistros',
            'Financeiro',
            'Cliente'
        ];

        //criar outros usuários comuns
        $usuariosCriados = User::factory()->count(20)->create();

        //atrelar os usuarios as filiais
        foreach ($usuariosCriados as $user){

            $filialSorteada = $filiais->random();

            $perfilSorteado = $perfis[array_rand($perfis)];

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => $perfilSorteado
            ]);

            $user->assignRole($perfilSorteado);
        
        }

        //criando especificamente corretores
        $usuariosCorretores = User::factory()->count(50)->create();

        foreach ($usuariosCorretores as $user){
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Corretor'
            ]);

            $user->assignRole('Corretor');
        }

        $usuariosClientes = User::factory()->count(70)->create();
        foreach ($usuariosClientes as $user){
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Cliente'
            ]);

            $user->assignRole('Cliente');
        }

    }
}
