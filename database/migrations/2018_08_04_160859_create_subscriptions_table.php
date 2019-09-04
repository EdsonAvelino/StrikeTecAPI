<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('SKU',16)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('tutorials',16)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('tournaments',16)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('battles',16)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->text('tournament_details')->charset('latin1')->collate('swedish_ci')->nullable();
                $table->text('battle_details')->charset('latin1')->collate('swedish_ci')->nullable();
                $table->text('tutorial_details')->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('name',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('duration',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->float('price')->nullable();
                $table->tinyInteger('status');
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
        Schema::dropIfExists('subscriptions');
    }
}
