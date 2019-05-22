<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignTypeIdRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('ratings', function (Blueprint $table) {
            // $table->integer('type_id')->unsigned()->change();
            // $table->foreign('type_id')
            //     ->references('id')->on('types')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ratings', function (Blueprint $table) {
            // $table->dropForeign('ratings_type_id_foreign');
        });
    }
}
