<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignEventSessionIdEventSessionPunchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('event_session_punches', function (Blueprint $table) {
            // $table->integer('event_session_id')->unsigned()->change();
            // $table->foreign('event_session_id')
            //     ->references('id')->on('event_sessions')
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
        Schema::table('event_session_punches', function (Blueprint $table) {
            // $table->dropForeign('event_session_punches_event_session_id_foreign');
        });
    }
}
