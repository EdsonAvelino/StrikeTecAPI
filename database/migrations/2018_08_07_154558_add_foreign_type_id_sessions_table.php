<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignTypeIdSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('sessions', function (Blueprint $table) {
            // $table->integer('type_id')->unsigned()->change();
            // $table->foreign('type_id')
            //     ->references('id')->on('types')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            // $table->dropForeign('sessions_type_id_foreign');
        });
    }
}
