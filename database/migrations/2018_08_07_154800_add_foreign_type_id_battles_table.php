<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignTypeIdBattlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // \DB::table('battles')->where('user_finished_at', '0000-00-00 00:00:00')->update(['user_finished_at' => null]);
        // \DB::table('battles')->where('opponent_finished_at', '0000-00-00 00:00:00')->update(['opponent_finished_at' => null]);

        Schema::table('battles', function (Blueprint $table) {
            // $table->integer('type_id')->unsigned()->change();
            // $table->foreign('type_id')
            //     ->references('id')->on('types')
            //     ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('battles', function (Blueprint $table) {
            // $table->dropForeign('battles_type_id_foreign');
        });
    }
}
