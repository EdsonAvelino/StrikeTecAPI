<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('notification_settings')) {
            Schema::create('notification_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('new_challenges')->default(1);
                $table->unsignedTinyInteger('battle_update')->default(1);
                $table->unsignedTinyInteger('tournaments_update')->default(1);
                $table->unsignedTinyInteger('games_update')->default(1);
                $table->unsignedTinyInteger('new_message')->default(1);
                $table->unsignedTinyInteger('friend_invites')->default(1);
                $table->unsignedTinyInteger('sensor_connect')->default(1);
                $table->unsignedTinyInteger('app_updates')->default(1);
                $table->unsignedTinyInteger('striketec_promos')->default(1);
                $table->unsignedTinyInteger('striketec_news')->default(1);
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
        Schema::dropIfExists('notification_settings');
    }
}
