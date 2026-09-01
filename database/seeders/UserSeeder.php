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
        if (Filial::count() === 0) {
            Filial::factory()->count(3)->create();
        }

        $filiais = Filial::all();

        $superAdmin = User::firstOrCreate(
            ['email' => 'super_admin@exemplo.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'status' => true,
            ]
        );
        $superAdmin->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@geral.com'],
            [
                'name' => 'Admin Geral',
                'password' => 'password',
                'status' => true,
            ]
        );
        $admin->assignRole('Administrador Geral');

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

            if (!$user->filiais()->exists()) {
                $user->filiais()->attach($filialPadrao->id, [
                    'perfil_acesso' => $dados['perfil']
                ]);
            }

            $user->assignRole($dados['perfil']);
        }

        $perfisOperacionais = [
            'Gestor de Filial', 
            'Subscritor',
            'Analista de Sinistros',
            'Financeiro'
        ];

        foreach ($filiais as $filial) {
            foreach ($perfisOperacionais as $perfil) {
                $user = User::factory()->create([
                    'status' => true,
                ]);

                $user->filiais()->attach($filial->id, [
                    'perfil_acesso' => $perfil
                ]);

                $user->assignRole($perfil);
            }
        }

        $usuariosCorretores = User::factory()->count(30)->create();
        foreach ($usuariosCorretores as $user) {
            $filialSorteada = $filiais->random();

            $user->filiais()->attach($filialSorteada->id, [
                'perfil_acesso' => 'Corretor'
            ]);

            $user->assignRole('Corretor');
        }
    }
}