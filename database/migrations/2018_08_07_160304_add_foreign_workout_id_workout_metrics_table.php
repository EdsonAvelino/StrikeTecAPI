<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignWorkoutIdWorkoutMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workout_metrics', function (Blueprint $table) {
            // $table->integer('workout_id')->unsigned()->change();
            // $table->foreign('workout_id')
            //     ->references('id')->on('workouts')
            //     ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workout_metrics', function (Blueprint $table) {
            // $table->dropForeign('workout_metrics_workout_id_foreign');
        });
    }
}
