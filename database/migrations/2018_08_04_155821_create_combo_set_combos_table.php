<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboSetCombosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combo_set_combos')) {
            Schema::create('combo_set_combos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedTinyInteger('combo_set_id');
                $table->unsignedTinyInteger('combo_id');
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
        Schema::dropIfExists('combo_set_combos');
    }
}
