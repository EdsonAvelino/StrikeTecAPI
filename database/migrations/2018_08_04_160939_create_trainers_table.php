<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('trainers')) {
            Schema::create('trainers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedTinyInteger('type')->nullable();
                $table->string('first_name',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('last_name',32)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->enum('gender',['male','female'])->charset('latin1')->collate('swedish_ci')->nullable();
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
        Schema::dropIfExists('trainers');
    }
}
