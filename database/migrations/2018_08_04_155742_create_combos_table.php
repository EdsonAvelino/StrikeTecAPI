<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCombosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combos')) {
            Schema::create('combos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('trainer_id')->nullable();
                $table->string('name',64)->charset('latin1')->collate('swedish_ci');
                $table->string('description')->nullable()->charset('latin1')->collate('swedish_ci');
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
        Schema::dropIfExists('combos');
    }
}
