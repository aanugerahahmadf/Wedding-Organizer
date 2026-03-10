<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles — super_admin = Admin Devi, user = Pelanggan yang memesan
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
    }
}
