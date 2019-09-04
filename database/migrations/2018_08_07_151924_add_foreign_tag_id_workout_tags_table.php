<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignTagIdWorkoutTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workout_tags', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `workout_tags` MODIFY `tag_id` INT(10) UNSIGNED');
            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workout_tags', function (Blueprint $table) {
            $table->dropForeign('workout_tags_tag_id_foreign');
        });
    }
}
