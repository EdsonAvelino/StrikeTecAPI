<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('event_activities')) {
            Schema::create('event_activities', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('event_id');
                $table->unsignedTinyInteger('event_activity_type_id');
                $table->unsignedTinyInteger('status')->default(0);
                $table->dateTime('concluded_at');
                // $table->dateTime('concluded_at')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_activities');
    }
}
