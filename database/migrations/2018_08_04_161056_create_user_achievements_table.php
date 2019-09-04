<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->smallInteger('achievement_id');
                $table->smallInteger('achievement_type_id')->nullable();
                $table->tinyInteger('awarded')->default(0);
                $table->tinyInteger('shared')->default(0);
                $table->mediumInteger('metric_value')->nullable()->default(0);
                $table->smallInteger('count')->default(0);
                $table->bigInteger('session_id')->nullable();
                $table->bigInteger('goal_id')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_achievements');
    }
}
