<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mr-lana.com'],
            [
                'name' => 'Admin Mr. Lana',
                'password' => Hash::make('password'),
                'rol' => User::ROL_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'vendedor@mr-lana.com'],
            [
                'name' => 'Vendedor Mr. Lana',
                'password' => Hash::make('password'),
                'rol' => User::ROL_VENDEDOR,
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            ProductoSeeder::class,
        ]);
    }
}
