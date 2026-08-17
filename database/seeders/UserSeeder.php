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
        // Garante que temos filiais cadastradas para fazer os vínculos
        if (Filial::count() === 0) {
            Filial::factory()->count(3)->create();
        }

        $filiais = Filial::all();

        // 1. CRIAR O ADMINISTRADOR GERAL (Global, sem vínculo restrito de filial)
        $admin = User::firstOrCreate(
            ['email' => 'admin_test@exemplo.com'],
            [
                'name' => 'Admin Geral',
                'password' => 'password',
                'status' => true,
            ]
        );
        $admin->assignRole('super_admin');

        // 2. CRIAR USUÁRIOS FIXOS DE TESTE PARA CADA PERFIL
        $perfisFixos = [
            ['name' => 'Gestor Teste', 'email' => 'gestor@filial.com', 'perfil' => 'Gestor de Filial'],
            ['name' => 'Subscritor Teste', 'email' => 'subscritor@seguradora.com', 'perfil' => 'Subscritor'],
            ['name' => 'Corretor Teste', 'email' => 'corretor@seguradora.com', 'perfil' => 'Corretor'],
            ['name' => 'Analista de Sinistros Teste', 'email' => 'analista@sinistros.com', 'perfil' => 'Analista de Sinistros'],
            ['name' => 'Financeiro Teste', 'email' => 'financeiro@seguradora.com', 'perfil' => 'Financeiro'],
            ['name' => 'Cliente Teste', 'email' => 'cliente@seguradora.com', 'perfil' => 'Cliente'],
        ];

        $filialPadrao = $filiais->first();

        foreach ($perfisFixos as $dados) {
            $user = User::firstOrCreate(
                ['email' => $dados['email']],
                [
                    'name' => $dados['name'],
                    'password' => 'password',
                    'status' => true,
                ]
            );

            // Vincula à primeira filial se ainda não estiver vinculado
            if (!$user->filiais()->exists()) {
                $user->filiais()->attach($filialPadrao->id, [
                    'perfil_acesso' => $dados['perfil']
                ]);
            }

            $user->assignRole($dados['perfil']);
        }

        // 3. DISTRIBUIR PERFIS OBRIGATÓRIOS PARA TODAS AS FILIAIS (Garantindo cobertura total)
        $perfisOperacionais = [
            'Gestor de Filial', 
            'Subscritor',
            'Analista de Sinistros',
            'Financeiro'
        ];

        foreach ($filiais as $filial) {
            foreach ($perfisOperacionais as $perfil) {
                // Cria um usuário único para aquele cargo naquela filial específica
                $user = User::factory()->create([
                    'status' => true,
                ]);

                $user->filiais()->attach($filial->id, [
                    'perfil_acesso' => $perfil
                ]);

                $user->assignRole($perfil);
            }
        }

        // 4. CRIAR MASSA DE CORRETORES (Distribuídos aleatoriamente pelas filiais)
        $usuariosCorretores = User::factory()->count(30)->create();
        foreach ($usuariosCorretores as $user) {
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Corretor'
            ]);

            $user->assignRole('Corretor');
        }

        // 5. CRIAR MASSA DE CLIENTES (Segurados)
        $usuariosClientes = User::factory()->count(50)->create();
        foreach ($usuariosClientes as $user) {
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Cliente'
            ]);

            $user->assignRole('Cliente');
        }
    }
}