<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignComboSetIdComboSetTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('combo_set_tags', function (Blueprint $table) {
            // $table->integer('combo_set_id')->unsigned()->change();
            // $table->foreign('combo_set_id')
            //     ->references('id')->on('combo_sets')
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
        Schema::table('combo_set_tags', function (Blueprint $table) {
            // $table->dropForeign('combo_set_tags_combo_set_id_foreign');
        });
    }
}
