<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('facebook_id')->nullable();
                // $table->integer('company_id')->nullable();
                $table->string('first_name',48)->charset('utf8')->collate('general_ci')->nullable();
                $table->string('last_name',48)->charset('utf8')->collate('general_ci')->nullable();
                $table->string('email',64)->charset('latin1')->collate('swedish_ci')->unique();
                $table->string('password',256)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->enum('gender',['male','female'])->charset('latin1')->collate('swedish_ci')->nullable();
                $table->dateTime('birthday')->nullable();
                $table->unsignedInteger('weight')->nullable();
                $table->unsignedTinyInteger('height_feet')->nullable();
                $table->float('height_inches',3,2)->nullable();
                $table->string('left_hand_sensor',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('right_hand_sensor',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('left_kick_sensor',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('right_kick_sensor',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->tinyInteger('is_spectator')->nullable();
                $table->tinyInteger('is_sharing_sensors')->nullable();
                $table->string('stance',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->tinyInteger('show_tip')->nullable();
                $table->string('skill_level',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('photo_url',256)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->unsignedSmallInteger('city_id')->nullable();
                $table->unsignedSmallInteger('state_id')->nullable();
                $table->tinyInteger('country_id')->nullable();
                $table->tinyInteger('membership_plan_id')->nullable();
                $table->dateTime('membership_plan_assigned_at')->nullable();
                $table->dateTime('membership_plan_removed_at')->nullable();
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
        Schema::dropIfExists('users');
    }
}
