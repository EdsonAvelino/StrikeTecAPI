<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('workout_tags')) {
            Schema::create('workout_tags', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('workout_id');
                $table->unsignedMediumInteger('tag_id');
                $table->tinyInteger('filter_id');
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
        Schema::dropIfExists('workout_tags');
    }
}
