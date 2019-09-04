<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignPunchTypeIdComboKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('combo_keys', function (Blueprint $table) {
            // $table->integer('punch_type_id')->unsigned()->change();
            // $table->foreign('punch_type_id')
            //     ->references('id')->on('punch_types')
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
        Schema::table('combo_keys', function (Blueprint $table) {
            // $table->dropForeign('combo_keys_punch_type_id_foreign');
        });
    }
}
