<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAchievementTypeIdUserAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            // $table->integer('achievement_type_id')->unsigned()->change();
            // $table->foreign('achievement_type_id')
            //     ->references('id')->on('achievements')
            //     ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            // $table->dropForeign('user_achievements_achievement_type_id_foreign');
        });
    }
}
