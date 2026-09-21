<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            RolesSeeder::class,
            FilialSeeder::class,
            UserSeeder::class,
            SeguradoSeeder::class,
            CoberturaSeeder::class,
            ProdutoSeeder::class,
        ]);

        $this->call([
            CotacaoSeeder::class,
            ApoliceSeeder::class,
            SinistroSeeder::class,
        ]);
    }
}
