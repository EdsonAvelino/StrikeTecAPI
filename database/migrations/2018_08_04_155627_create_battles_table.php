<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBattlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('battles')) {
            Schema::create('battles', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('opponent_user_id');
                $table->integer('plan_id')->nullable();
                $table->unsignedInteger('type_id')->nullable();
                $table->unsignedTinyInteger('accepted')->nullable();
                $table->dateTime('accepted_at')->nullable();
                $table->unsignedTinyInteger('user_finished')->nullable();
                $table->unsignedTinyInteger('opponent_finished')->nullable();
                $table->dateTime('user_finished_at')->nullable();
                $table->dateTime('opponent_finished_at')->nullable();
                $table->unsignedInteger('winner_user_id')->nullable();
                $table->unsignedTinyInteger('user_shared')->nullable();
                $table->unsignedTinyInteger('opponent_shared')->nullable();
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
        Schema::dropIfExists('battles');
    }
}
