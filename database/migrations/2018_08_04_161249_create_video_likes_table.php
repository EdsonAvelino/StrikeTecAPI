<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideoLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('video_likes')) {
            Schema::create('video_likes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('video_id')->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->dateTime('liked_at')->nullable();
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
        Schema::dropIfExists('video_likes');
    }
}
