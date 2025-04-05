<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UltraAuthSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUserIfNotExists(
            'admin@ultra.dev',
            'Admin',
            'password',
            'ConfigManager'
        );

        $this->createUserIfNotExists(
            'viewer@ultra.dev',
            'Viewer',
            'password',
            'ConfigViewer'
        );

        $this->createUserIfNotExists(
            'anon@ultra.dev',
            'Anon',
            'password',
            'User'
        );
    }

    protected function createUserIfNotExists(string $email, string $name, string $rawPassword, string $role): void
    {
        if (!User::where('email', $email)->exists()) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($rawPassword),
                'role' => $role,
            ]);
        }
    }
}
