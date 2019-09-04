<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedTinyInteger('public_profile')->nullable();
                $table->unsignedTinyInteger('show_achivements')->nullable();
                $table->unsignedTinyInteger('show_training_stats')->nullable();
                $table->unsignedTinyInteger('show_challenges_history')->nullable();
                $table->unsignedTinyInteger('badge_notification')->nullable();
                $table->unsignedTinyInteger('show_tutorial')->nullable();
                $table->unsignedTinyInteger('unit')->nullable();
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
        Schema::dropIfExists('user_preferences');
    }
}
