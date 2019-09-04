<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventActivityTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('event_activity_types')) {
            Schema::create('event_activity_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name',16)->charset('latin1')->collate('swedish_ci');
                $table->string('description',56)->charset('latin1')->collate('swedish_ci');
                $table->string('image_url',128)->charset('latin1')->collate('swedish_ci');
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
        Schema::dropIfExists('event_activity_types');
    }
}
