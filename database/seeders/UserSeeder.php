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
        User::factory()->create([
            'name' => 'Admin Geral',
            'email' => 'admin_test@exemplo.com',
            'password' => 'password',
            'status' => true,
        ]);

        $filiais = Filial::all();

        $perfis = [
            'Gestor de Filial', 
            'Subscritor',
            'Corretor',
            'Analista de Sinistro',
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
        
        }

        //criando especificamente corretores
        $usuariosCorretores = User::factory()->count(50)->create();

        foreach ($usuariosCorretores as $user){
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Corretor'
            ]);
        }

        $usuariosClientes = User::factory()->count(70)->create();
        foreach ($usuariosClientes as $user){
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Cliente'
            ]);
        }
    }
}
