<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignWorkoutIdWorkoutRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('workout_rounds', function (Blueprint $table) {
            // $table->integer('workout_id')->unsigned()->change();
            // $table->foreign('workout_id')
            //     ->references('id')->on('workouts')
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
        Schema::table('workout_rounds', function (Blueprint $table) {
            // $table->dropForeign('workout_rounds_workout_id_foreign');
        });
    }
}
