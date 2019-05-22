<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignGoalIdUserAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('user_achievements', function (Blueprint $table) {
            // $table->integer('goal_id')->unsigned()->change();
            // $table->foreign('goal_id')
            //     ->references('id')->on('goals')
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
        Schema::table('user_achievements', function (Blueprint $table) {
            // $table->dropForeign('user_achievements_goal_id_foreign');
        });
    }
}
