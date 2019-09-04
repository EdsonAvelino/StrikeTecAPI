<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('workout_metrics')) {
            Schema::create('workout_metrics', function (Blueprint $table) {
                $table->increments('id');
                $table->mediumInteger('workout_id')->nullable();
                $table->string('metric', 20)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->mediumInteger('min')->nullable();
                $table->mediumInteger('max')->nullable();
                $table->mediumInteger('interval')->nullable();
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
        Schema::dropIfExists('workout_metrics');
    }
}
