<?php

test ('basic math must work correctly', function () {
    $result = 10 + 10; //act

    expect($result)->toBe(20); //assert
});

test ('model user must instance a user correctly', function () {
    //Arrange
    $user = new \App\Models\User();
    $user->name = 'K.S. Bomfim';

    //act
    $nomeObtido = $user->name;

    //assert
    expect($nomeObtido)->toBe('K.S. Bomfim');
    expect ($user)->toBeInstanceOf(\App\Models\User::class);

});
