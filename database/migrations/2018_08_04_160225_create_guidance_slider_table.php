<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGuidanceSliderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('guidance_slider')) {
            Schema::create('guidance_slider', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->binary('description')->nullable();
                $table->unsignedTinyInteger('type_id')->nullable();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedTinyInteger('order')->nullable();
                $table->unsignedTinyInteger('status')->nullable();
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
        Schema::dropIfExists('guidance_slider');
    }
}
