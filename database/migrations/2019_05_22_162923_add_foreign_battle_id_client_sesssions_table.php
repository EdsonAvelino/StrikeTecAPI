<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignBattleIdClientSesssionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('client_sessions', function (Blueprint $table) {
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
        Schema::table('client_sessions', function (Blueprint $table) {
            $table->dropForeign('client_sessions_battle_id_foreign');
        });
    }
}
