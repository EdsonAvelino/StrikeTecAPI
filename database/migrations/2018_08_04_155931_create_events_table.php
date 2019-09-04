<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id')->nullable();
                $table->unsignedInteger('admin_user_id')->nullable();
                $table->unsignedInteger('location_id')->nullable();
                $table->string('title',48)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('description',50)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('image',128)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->dateTime('starting_at')->nullable();
                $table->dateTime('ending_at')->nullable();
                $table->unsignedTinyInteger('all_day')->nullable();
                $table->unsignedTinyInteger('status')->nullable()->default(1);
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
        Schema::dropIfExists('events');
    }
}
