<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminAccessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('admin_access_logs')) {
            Schema::create('admin_access_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('admin_user_id')->nullable();
                $table->string('ip', 32)->nullable()->charset('latin1')->collate('swedish_ci');
                $table->string('user_agent')->nullable()->charset('latin1')->collate('swedish_ci');
                $table->dateTime('login_at')->nullable();
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
        Schema::dropIfExists('admin_access_logs');
    }
}
