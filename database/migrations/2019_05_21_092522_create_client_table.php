<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 48)->nullable();
            $table->string('last_name', 48)->nullable();
            $table->unsignedInteger('coach_user')->nullable();
            $table->foreign('coach_user')->references('id')->on('users');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthday')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedTinyInteger('height_feet')->nullable();
            $table->float('height_inches', 3, 2)->nullable();
            $table->string('left_hand_sensor', 32)->nullable();
            $table->string('right_hand_sensor', 32)->nullable();
            $table->string('left_kick_sensor', 32)->nullable();
            $table->string('right_kick_sensor', 32)->nullable();
            $table->boolean('is_spectator')->nullable();
            $table->boolean('is_sharing_sensors')->nullable();
            $table->string('stance', 32)->nullable();
            $table->boolean('show_tip')->nullable();
            $table->string('skill_level', 32)->nullable();
            $table->string('photo_url', 256)->nullable();
            $table->unsignedSmallInteger('city_id')->nullable();
            $table->unsignedSmallInteger('state_id')->nullable();
            $table->unsignedTinyInteger('country_id')->nullable();
            $table->boolean('has_sensors')->default(0);
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('clients');
    }
}
