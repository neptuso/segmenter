<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\User;

class CreateRolAdminRadios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call( 'db:seed', [
            '--class' => 'RadioPermissionSeeder',
            '--force' => true ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();
        /** le quito el rol a quienes lo tengan */
        $radioAdmins = User::role('Radios Admin')->get();
        foreach ($radioAdmins as $r){
            $r->removeRole('Super Admin');
        }
        
        /** le quito los permisos al rol y lo elimino*/
        $rol = Role::where(['name'=>'Radios Admin'])->first();
        $rol->syncPermissions([]);
        $rol->delete();

        /** elimino los permisos */
        $desvincularRadios = Permission::where(['name'=>'Desvincular Radio Localidad'])->first()->delete();
        $modificarRadios = Permission::where(['name'=>'Modificar Tipo Radio'])->first()->delete();
        $eliminarRadios = Permission::where(['name'=>'Eliminar Radio'])->first()->delete();
        DB::commit();
    }
}
