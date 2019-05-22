<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignComboIdWorkoutRoundCombosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('workout_round_combos', function (Blueprint $table) {
            // $table->integer('combo_id')->unsigned()->change();
            // $table->foreign('combo_id')
            //     ->references('id')->on('combos')
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
        Schema::table('workout_round_combos', function (Blueprint $table) {
            // $table->dropForeign('workout_round_combos_combo_id_foreign');
        });
    }
}
