<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignGoalIdGoalSessionsTable extends Migration
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
            // $table->integer('goal_id')->unsigned()->change();
            // $table->foreign('goal_id')
            //     ->references('id')->on('goals')
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
            // $table->dropForeign('goal_sessions_goal_id_foreign');
        });
    }
}
