<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignChatIdChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('chat_messages', function (Blueprint $table) {
            // $table->integer('chat_id')->unsigned()->change();
            // $table->foreign('chat_id')
            //     ->references('id')->on('chats')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET GLOBAL FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // $table->dropForeign('chat_messages_chat_id_foreign');
        });
    }
}
