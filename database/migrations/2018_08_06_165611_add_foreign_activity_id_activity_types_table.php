<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignActivityIdActivityTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_types', function (Blueprint $table) {
            // $table->integer('activity_id')->unsigned()->change();
            // $table->foreign('activity_id')
            //     ->references('id')->on('activities')
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
        Schema::table('activity_types', function (Blueprint $table) {
            // $table->dropForeign('activity_types_activity_id_foreign');
        });
    }
}
