<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignPostIdPostCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('post_comments', function (Blueprint $table) {
            // $table->integer('post_id')->unsigned()->change();
            // $table->foreign('post_id')
            //     ->references('id')->on('posts')
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
        Schema::table('post_comments', function (Blueprint $table) {
            // $table->dropForeign('post_comments_post_id_foreign');
        });
    }
}
