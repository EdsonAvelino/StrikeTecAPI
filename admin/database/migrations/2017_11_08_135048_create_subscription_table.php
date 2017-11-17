<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::Create('subscriptions', function(Blueprint $table) {
            $table->increments('id');
            $table->string('SKU', 20)->nullable();
            $table->string('tutorials', 20)->nullable();
            $table->string('tournaments', 20)->nullable();
            $table->string('battles', 20)->nullable();
            $table->text('tournament_details')->nullable();
            $table->text('battle_details')->nullable();
            $table->text('tutorial_details')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->float('price')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('modified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExist('subscriptions');
    }
}