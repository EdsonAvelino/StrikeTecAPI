<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignTagFilterIdVideoTagFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('video_tag_filters', function (Blueprint $table) {
            // $table->integer('tag_filter_id')->unsigned()->change();
            // $table->foreign('tag_filter_id')
            //     ->references('id')->on('tag_filters')
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
        Schema::table('video_tag_filters', function (Blueprint $table) {
            // $table->dropForeign('video_tag_filters_tag_filter_id_foreign');
        });
    }
}
