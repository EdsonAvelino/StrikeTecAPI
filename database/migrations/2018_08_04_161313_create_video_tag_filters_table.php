<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideoTagFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('video_tag_filters')) {
            Schema::create('video_tag_filters', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedMediumInteger('video_id')->nullable();
                $table->unsignedTinyInteger('tag_filter_id')->nullable();
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
        Schema::dropIfExists('video_tag_filters');
    }
}
