<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

test('deve salvar um usuário no banco de dados corretamente', function() {
    //Arrange
    $user = new User();
    $user->name = 'Analista';
    $user->email = 'qa@seguradora.com';
    $user->password = bcrypt('password');

    //Act
    $user->save();

    //Assert
    $this->assertDatabaseHas('users', [
        'email' => 'qa@seguradora.com'
    ]);
});