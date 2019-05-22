<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventSessionPunchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('event_session_punches')) {
            Schema::create('event_session_punches', function (Blueprint $table) {
                $table->increments('id');
                $table->bigInteger('event_session_id');
                $table->double('punch_time')->nullable();
                $table->decimal('punch_duration',6,0)->nullable();
                $table->double('force')->nullable();
                $table->double('speed')->nullable();
                $table->string('punch_type',3)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('hand',1)->charset('latin1')->collate('swedish_ci')->nullable();
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
        Schema::dropIfExists('event_session_punches');
    }
}
