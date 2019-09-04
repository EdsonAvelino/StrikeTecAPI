<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignVideoViews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->change();
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('video_views', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
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
        Schema::table('video_views', function (Blueprint $table) {
            $table->dropForeign('battles_user_id_foreign');
        });
    }
}
