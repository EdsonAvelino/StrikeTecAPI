<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboSetTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combo_set_tags')) {
            Schema::create('combo_set_tags', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('combo_set_id');
                $table->unsignedMediumInteger('tag_id');
                $table->smallInteger('filter_id');
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
        Schema::dropIfExists('combo_set_tags');
    }
}
