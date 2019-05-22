<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionRoundPunchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('session_round_punches')) {
            Schema::create('session_round_punches', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('session_round_id')->nullable();
                $table->bigInteger('punch_time')->nullable();
                $table->decimal('punch_duration',6,3)->nullable();
                $table->double('force')->nullable();
                $table->double('speed')->nullable();
                $table->string('punch_type',3)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->char('hand',1)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->float('distance',6,1)->nullable();
                $table->tinyInteger('is_correct')->nullable();
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
        Schema::dropIfExists('session_round_punches');
    }
}
