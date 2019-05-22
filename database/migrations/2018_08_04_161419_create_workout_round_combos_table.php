<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutRoundCombosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('workout_round_combos')) {
            Schema::create('workout_round_combos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('workout_round_id');
                $table->unsignedTinyInteger('combo_id');
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
        Schema::dropIfExists('workout_round_combos');
    }
}
