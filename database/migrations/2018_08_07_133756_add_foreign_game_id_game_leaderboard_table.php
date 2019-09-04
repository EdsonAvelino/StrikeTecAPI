<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignGameIdGameLeaderboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('game_leaderboard', function (Blueprint $table) {
            // $table->integer('game_id')->unsigned()->change();
            // $table->foreign('game_id')
            //     ->references('id')->on('games')
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
        Schema::table('game_leaderboard', function (Blueprint $table) {
            // $table->dropForeign('game_leaderboard_game_id_foreign');
        });
    }
}
