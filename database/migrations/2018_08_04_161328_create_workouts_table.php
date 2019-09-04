<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('workouts')) {
            Schema::create('workouts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('trainer_id')->nullable();
                $table->string('name',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('description',256)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->unsignedSmallInteger('round_time')->nullable();
                $table->unsignedSmallInteger('rest_time')->nullable();
                $table->unsignedSmallInteger('prepare_time')->nullable();
                $table->unsignedSmallInteger('warning_time')->nullable();
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
        Schema::dropIfExists('workouts');
    }
}
