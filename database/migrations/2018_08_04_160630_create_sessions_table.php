<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('battle_id')->nullable();
                $table->unsignedTinyInteger('game_id')->nullable();
                $table->unsignedTinyInteger('type_id')->nullable();
                $table->bigInteger('start_time')->nullable();
                $table->bigInteger('end_time')->nullable();
                $table->tinyInteger('plan_id')->nullable();
                $table->double('avg_speed',10,2)->nullable();
                $table->double('avg_force',10,2)->nullable();
                $table->unsignedSmallInteger('punches_count')->nullable();
                $table->double('max_speed',10,2)->nullable();
                $table->double('max_force',10,2)->nullable();
                $table->decimal('best_time',6,2)->nullable();
                $table->unsignedTinyInteger('shared')->nullable();
                $table->unsignedTinyInteger('is_archived')->nullable();
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
        Schema::dropIfExists('sessions');
    }
}
