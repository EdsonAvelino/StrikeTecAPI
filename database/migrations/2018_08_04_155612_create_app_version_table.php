<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('app_version')) {
            Schema::create('app_version', function (Blueprint $table) {
            $table->increments('id');
            $table->string('android_v',16)->charset('latin1')->collate('swedish_ci');
            $table->string('ios_v',16)->charset('latin1')->collate('swedish_ci');
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
        Schema::dropIfExists('app_version');
    }
}
