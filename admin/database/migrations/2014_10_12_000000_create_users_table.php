<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersTable.
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('facebook_id')->nullable();
            $table->string('first_name', 48)->nullable();
            $table->string('last_name', 48)->nullable();
            $table->string('email', 64)->unique();
            $table->string('password', 256)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthday')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('height')->nullable();
            $table->string('left_hand_sensor', 32)->nullable();
            $table->string('right_hand_sensor', 32)->nullable();
            $table->string('left_kick_sensor', 32)->nullable();
            $table->string('right_kick_sensor', 32)->nullable();
            $table->boolean('is_spectator')->nullable();
            $table->string('stance', 32)->nullable();
            $table->boolean('show_tip')->nullable();
            $table->string('skill_level', 32)->nullable();
            $table->string('photo_url', 256)->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('country_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
