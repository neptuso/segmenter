<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartamentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
     /**
        Schema::create('provincia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

        });
     **/
        // SI ya no esta la tabla de departamentos.
        if (! Schema::hasTable('departamentos')){
	Schema::create('departamentos', function (Blueprint $table) {
		$table->bigIncrements('id')->index();
		$table->string('codigo')->index();
		$table->string('nombre')->index();
		$table->integer('provincia_id')->index();
		$table->date('fecha_desde')->nullable();
		$table->date('fecha_hasta')->nullable();
		$table->integer('observacion_id')->nullable();
		$table->integer('geometria_id')->nullable();
		$table->foreign('provincia_id')->references('id')->on('provincia')->onDelete('cascade');
		//$table->timestamps();
	});
        }else{
             echo 'No se crea tabla de departamentos xq ya se encuentra una.
';
        }	
/*
	 $sql = file_get_contents(app_path() . '/developer_docs/departamentos.up.sql');
	 DB::unprepared($sql);
*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('departamentos');
    }
}
