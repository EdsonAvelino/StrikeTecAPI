<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignSessionIdSessionRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('session_rounds', function (Blueprint $table) {
            // $table->integer('session_id')->unsigned()->change();
            // $table->foreign('session_id')
            //     ->references('id')->on('sessions')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('session_rounds', function (Blueprint $table) {
            // $table->dropForeign('session_rounds_session_id_foreign');
        });
    }
}
