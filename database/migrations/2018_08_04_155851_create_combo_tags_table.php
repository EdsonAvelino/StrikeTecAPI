<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combo_tags')) {
            Schema::create('combo_tags', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedTinyInteger('combo_id');
                $table->unsignedTinyInteger('tag_id');
                $table->tinyInteger('filter_id')->nullable();
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
        Schema::dropIfExists('combo_tags');
    }
}
