<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('session_rounds')) {
            Schema::create('session_rounds', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('session_id')->nullable();
                $table->bigInteger('start_time')->nullable();
                $table->bigInteger('end_time')->nullable();
                $table->unsignedBigInteger('pause_duration')->nullable();
                $table->double('avg_speed',10,2)->nullable();
                $table->double('avg_force',10,2)->nullable();
                $table->unsignedSmallInteger('punches_count')->nullable();
                $table->double('max_speed',10,2)->nullable();
                $table->double('max_force',10,2)->nullable();
                $table->decimal('best_time',6,2)->nullable();
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
        Schema::dropIfExists('session_rounds');
    }
}
