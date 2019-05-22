<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('event_sessions')) {
            Schema::create('event_sessions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('participant_id')->nullable();
                $table->integer('event_activity_id')->nullable();
                $table->integer('start_time')->nullable();
                $table->integer('end_time')->nullable();
                $table->integer('plan_id')->nullable();
                $table->double('avg_speed',10,2)->nullable();
                $table->double('avg_force',10,2)->nullable();
                $table->smallInteger('punches_count')->nullable();
                $table->double('max_speed',10,2)->nullable();
                $table->double('max_force',10,2)->nullable();
                $table->decimal('best_time',8,0)->nullable();
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
        Schema::dropIfExists('event_sessions');
    }
}
