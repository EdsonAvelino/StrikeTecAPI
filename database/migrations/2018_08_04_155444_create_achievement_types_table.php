<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('achievement_types')) {
            Schema::create('achievement_types', function (Blueprint $table) {
                $table->increments('id');
                $table->smallInteger('achievement_id');
                $table->string('name', 100);
                $table->string('description')->nullable();
                $table->string('image');
                $table->mediumInteger('config')->default(0);
                $table->mediumInteger('min')->default(0);
                $table->mediumInteger('max')->default(0);
                $table->mediumInteger('interval_value')->default(0);
                $table->string('gender', 10)->charset('latin1')->collate('swedish_ci')->nullable();
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
        Schema::dropIfExists('achievement_types');
    }
}
