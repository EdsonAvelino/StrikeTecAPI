<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignVideoIdVideoLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_likes', function (Blueprint $table) {
            $table->integer('video_id')->unsigned()->change();
            $table->foreign('video_id')
                ->references('id')->on('videos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('video_likes', function (Blueprint $table) {
            $table->dropForeign('video_likes_video_id_foreign');
        });
    }
}
