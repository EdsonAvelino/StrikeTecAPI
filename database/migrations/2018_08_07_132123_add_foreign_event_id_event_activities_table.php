<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignEventIdEventActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('event_activities', function (Blueprint $table) {
            // $table->integer('event_id')->unsigned()->change();
            // $table->foreign('event_id')
            //     ->references('id')->on('events')
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
        Schema::table('event_activities', function (Blueprint $table) {
            // $table->dropForeign('event_activities_event_id_foreign');
        });
    }
}
