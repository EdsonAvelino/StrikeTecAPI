<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('videos')) {
            Schema::create('videos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('trainer_id')->nullable();
                $table->unsignedTinyInteger('type_id')->nullable();
                $table->unsignedSmallInteger('plan_id')->nullable();
                $table->string('title',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('description',256)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('file',48)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('thumbnail',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('duration',8)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->unsignedInteger('views')->nullable();
                $table->unsignedTinyInteger('is_featured')->nullable();
                $table->tinyInteger('order')->nullable();
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
        Schema::dropIfExists('videos');
    }
}
