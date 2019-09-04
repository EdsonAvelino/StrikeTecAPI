<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignUserIdUserAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('user_achievements', function (Blueprint $table) {
            // \DB::statement('ALTER TABLE `user_achievements` MODIFY `updated_at` datetime NULL');
            // $table->integer('user_id')->unsigned()->change();
            // $table->foreign('user_id')
            //     ->references('id')->on('users')
            //     ->onDelete('cascade');
        });

        // \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Schema::table('user_achievements', function (Blueprint $table) {
        //     // $table->dropForeign('user_achievements_user_id_foreign');
        // });
        // \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
