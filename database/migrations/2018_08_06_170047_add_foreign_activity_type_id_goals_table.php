<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignActivityTypeIdGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goals', function (Blueprint $table) {
            // $table->integer('activity_type_id')->unsigned()->change();
            // $table->foreign('activity_type_id')
            //     ->references('id')->on('activity_types')
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
        Schema::table('goals', function (Blueprint $table) {
            // $table->dropForeign('goals_activity_type_id_foreign');
        });
    }
}
