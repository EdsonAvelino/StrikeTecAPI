<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedBigInteger('event_activity_id')->nullable();
                $table->unsignedTinyInteger('is_finished')->nullable();
                $table->string('auth_code',20)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('joined_via',1)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->dateTime('joined_at',1)->nullable();
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
        Schema::dropIfExists('event_participants');
    }
}
