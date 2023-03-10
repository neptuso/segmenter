<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RadioPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Creando permisos para Radios Admin...');
        try{
            $desvincularRadios = Permission::firstOrcreate(['name' => 'Desvincular Radio Localidad']);
            $modificarRadios = Permission::firstOrcreate(['name' => 'Modificar Tipo Radio']);
            $eliminarRadios = Permission::firstOrcreate(['name' => 'Eliminar Radio']);

            $this->command->info('Creando rol Radios Admin y asignando permisos...');
            $superAdmin = Role::firstOrcreate(['name' => 'Radios Admin'])->syncPermissions([$desvincularRadios, $modificarRadios, $eliminarRadios]);
            $this->command->info('Rol Radios Admin creado.');
        } catch ( Spatie\Permission\Exceptions $e) {
            $this->command->error('Error creando permisos del Radios Admin...');
            echo _($e->getMessage());
        }
    }
}
