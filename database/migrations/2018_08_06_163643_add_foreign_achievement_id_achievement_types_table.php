<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAchievementIdAchievementTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET GLOBAL FOREIGN_KEY_CHECKS=0');

        Schema::table('achievement_types', function (Blueprint $table) {
            // $table->integer('achievement_id')->unsigned()->change();
            // $table->foreign('achievement_id')
            //     ->references('id')->on('achievements')
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
        Schema::table('achievement_types', function (Blueprint $table) {
            // $table->dropForeign('achievement_types_achievement_id_foreign');
        });
    }
}
