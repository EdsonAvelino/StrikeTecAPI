<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignVideoIdVideoTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_tags', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `video_tags` MODIFY `video_id` INT(10) UNSIGNED');
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
        Schema::table('video_tags', function (Blueprint $table) {
            $table->dropForeign('video_tags_video_id_foreign');
        });
    }
}
