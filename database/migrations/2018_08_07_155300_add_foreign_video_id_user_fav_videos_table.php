<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignVideoIdUserFavVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('user_fav_videos', function (Blueprint $table) {
            // $table->integer('video_id')->unsigned()->change();
            // $table->foreign('video_id')
            //     ->references('id')->on('videos')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_fav_videos', function (Blueprint $table) {
            // $table->dropForeign('user_fav_videos_video_id_foreign');
        });
    }
}
