<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAglomeradosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SI ya no esta la tabla de aglomerados.
        if (! Schema::hasTable('aglomerados')){
	 $sql = file_get_contents(app_path() . '/developer_docs/aglomerados.up.sql');
	 DB::unprepared($sql);
        Schema::table('aglomerados', function (Blueprint $table) {
            $table->index(['id']);
	});
        }else{
             echo 'No se crea tabla de aglomerados xq ya se encuentra una.
';
        }
	 

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('aglomerados');
    }
}
