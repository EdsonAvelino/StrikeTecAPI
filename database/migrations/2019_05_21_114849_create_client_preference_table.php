<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientPreferenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('client_id')->nullable();
            $table->boolean('public_profile')->nullable();
            $table->boolean('show_achivements')->nullable();
            $table->boolean('show_training_stats')->nullable();
            $table->boolean('show_challenges_history')->nullable();
            $table->boolean('badge_notification')->nullable();
            $table->boolean('show_tutorial')->nullable();
            $table->boolean('unit')->nullable();
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
        Schema::drop('client_preferences');
    }
}
