<?php

use App\Models\Filial;
use App\Models\Segurado;
use App\Models\SeguradoPf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'Cliente', 'guard_name' => 'web']);
    Role::create(['name' => 'Corretor', 'guard_name' => 'web']);

    $this->filial = Filial::create([
        'nome' => 'Ciranna Yuci',
        'cnpj' => '12345678000190',
        'telefone' => '11987654321',
        'rua' => 'Rua Exemplo',
        'numero' => '123',
        'bairro' => 'Bairro Exemplo',
        'complemento' => 'Apto 101',
        'cidade' => 'Cidade Exemplo',
        'uf' => 'SP',
        'cep' => '12345678',
    ]);

    $this->corretor = User::create([
        'name' => 'Elean',
        'email' => 'elean@teste.com',
        'password' => bcrypt('password'),
    ]);

    $this->corretor->filiais()->attach($this->filial->id,
        ['perfil_acesso' => 'Corretor']
    );
    $this->corretor->assignRole('Corretor');

});

test('deve criar um usuário cliente vinculado a filial do corretor ao criar um segurado', function () {

    // Act
    $segurado = Segurado::create([
        'tipo' => 'pf',
        'telefone' => '11987654321',
        'email' => 'donhollister@cy.com',
        'rua' => 'Rua Exemplo',
        'numero' => '123',
        'bairro' => 'Bairro Exemplo',
        'complemento' => 'Apto 101',
        'cidade' => 'Cidade Exemplo',
        'uf' => 'SP',
        'cep' => '12345678',
        'score' => 750,
        'status' => 'ativo',
        'corretor_id' => $this->corretor->id,
    ]);

    SeguradoPf::create([
        'segurado_id' => $segurado->id,
        'cpf' => '77777777777',
        'rg' => '123456789',
        'nome' => 'Sennet Hollister',
        'data_nascimento' => '2004-05-07',
        'profissao' => 'Analistra de Sinistros',
    ]);

    // Assert
    $this->assertDatabaseHas('users', [
        'email' => 'donhollister@cy.com',
    ]);

    // o user criado está vinculado ao segurado
    $userCriado = User::where('email', 'donhollister@cy.com')->first();
    $this->assertDatabaseHas('segurados', [
        'id' => $segurado->id,
        'user_id' => $userCriado->id,
    ]);

    $this->assertDatabaseHas('filial_user', [
        'user_id' => $userCriado->id,
        'filial_id' => $this->filial->id,
        'perfil_acesso' => 'Cliente',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Sennet Hollister',
    ]);

    expect($userCriado->hasRole('Cliente'))->toBeTrue();
});
