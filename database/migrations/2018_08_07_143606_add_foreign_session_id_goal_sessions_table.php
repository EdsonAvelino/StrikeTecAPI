<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignSessionIdGoalSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('goal_sessions', function (Blueprint $table) {
            // $table->integer('session_id')->unsigned()->change();
            // $table->foreign('session_id')
            //     ->references('id')->on('sessions')
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
        Schema::table('goal_sessions', function (Blueprint $table) {
            // $table->dropForeign('goal_sessions_session_id_foreign');
        });
    }
}
