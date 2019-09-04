<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaderboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('leaderboard')) {
            Schema::create('leaderboard', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedMediumInteger('sessions_count')->nullable();
                $table->double('avg_speed',10,2)->nullable();
                $table->double('avg_force',10,2)->nullable();
                $table->unsignedInteger('punches_count')->nullable();
                $table->double('max_speed',10,2)->nullable();
                $table->double('max_force',10,2)->nullable();
                $table->unsignedMediumInteger('total_time_trained')->nullable();
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
        Schema::dropIfExists('leaderboard');
    }
}
