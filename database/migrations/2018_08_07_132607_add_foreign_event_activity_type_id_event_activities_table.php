<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignEventActivityTypeIdEventActivitiesTable extends Migration
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
            // $table->integer('event_activity_type_id')->unsigned()->change();
            // $table->foreign('event_activity_type_id')
            //     ->references('id')->on('event_activity_types')
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
            // $table->dropForeign('event_activities_event_activity_type_id_foreign');
        });
    }
}
