<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignBattleIdSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // \DB::table('battles')->where('user_finished_at', '0000-00-00 00:00:00')->update(['user_finished_at' => null]);
        // \DB::table('battles')->where('opponent_finished_at', '0000-00-00 00:00:00')->update(['opponent_finished_at' => null]);
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('sessions', function (Blueprint $table) {
            $table->integer('battle_id')->unsigned()->change();
            $table->foreign('battle_id')
                ->references('id')->on('battles')
                ->onDelete('cascade');
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
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign('sessions_battle_id_foreign');
        });
    }
}
