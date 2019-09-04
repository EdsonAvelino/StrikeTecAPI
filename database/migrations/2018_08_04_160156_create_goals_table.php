<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('goals')) {
            Schema::create('goals', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('activity_id');
                $table->unsignedTinyInteger('activity_type_id');
                $table->string('target',64)->charset('latin1')->collate('swedish_ci');
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->unsignedTinyInteger('followed')->default(0);
                $table->unsignedSmallInteger('done_count')->default(0);
                $table->dateTime('followed_at');
                $table->float('avg_time');
                $table->smallInteger('avg_speed');
                $table->smallInteger('avg_power');
                $table->smallInteger('achieve_type');
                $table->unsignedTinyInteger('shared')->default(0);
                $table->tinyInteger('awarded')->default(0);
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
        Schema::dropIfExists('goals');
    }
}
