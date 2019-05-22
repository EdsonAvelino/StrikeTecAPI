<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combo_sets')) {
            Schema::create('combo_sets', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('trainer_id')->nullable();
                $table->string('name', 64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('description')->charset('latin1')->collate('swedish_ci')->nullable();
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
        Schema::dropIfExists('combo_sets');
    }
}
