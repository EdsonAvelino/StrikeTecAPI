<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignSessionRoundIdSessionRoundPunchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('session_round_punches', function (Blueprint $table) {
            // $table->integer('session_round_id')->unsigned()->change();
            // $table->foreign('session_round_id')
            //     ->references('id')->on('session_rounds')
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
        Schema::table('session_round_punches', function (Blueprint $table) {
            // $table->dropForeign('session_round_punches_session_round_id_foreign');
        });
    }
}
