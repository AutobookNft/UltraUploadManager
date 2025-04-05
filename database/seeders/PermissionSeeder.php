<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Creare i permessi
        Permission::create(['name' => 'view-config']);
        Permission::create(['name' => 'create-config']);
        Permission::create(['name' => 'update-config']);
        Permission::create(['name' => 'delete-config']);

        // Creare i ruoli e assegnare i permessi
        $viewerRole = Role::create(['name' => 'ConfigViewer']);
        $viewerRole->givePermissionTo('view-config');

        $editorRole = Role::create(['name' => 'ConfigEditor']);
        $editorRole->givePermissionTo(['view-config', 'create-config', 'update-config']);

        $adminRole = Role::create(['name' => 'ConfigManager']);
        $adminRole->givePermissionTo(['view-config', 'create-config', 'update-config', 'delete-config']);
    }
}