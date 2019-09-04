<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWriteUsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('write_us')) {
            Schema::create('write_us', function (Blueprint $table) {
                $table->increments('id');
                $table->string('email',64)->charset('latin1')->collate('swedish_ci');
                $table->string('subject',128)->charset('latin1')->collate('swedish_ci');
                $table->text('message')->charset('latin1')->collate('swedish_ci');
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
        Schema::dropIfExists('write_us');
    }
}
