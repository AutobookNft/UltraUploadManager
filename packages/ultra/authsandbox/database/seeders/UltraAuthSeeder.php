<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UltraAuthSeeder extends Seeder
{
    public function run(): void
    {
        User::truncate();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@ultra.dev',
            'password' => Hash::make('password'),
            'role' => 'ConfigManager',
        ]);

        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@ultra.dev',
            'password' => Hash::make('password'),
            'role' => 'ConfigViewer',
        ]);

        User::create([
            'name' => 'Anon',
            'email' => 'anon@ultra.dev',
            'password' => Hash::make('password'),
            'role' => 'User',
        ]);
    }
}
