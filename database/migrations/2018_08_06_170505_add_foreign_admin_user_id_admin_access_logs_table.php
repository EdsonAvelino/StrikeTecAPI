<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAdminUserIdAdminAccessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_access_logs', function (Blueprint $table) {
            $table->integer('admin_user_id')->unsigned()->change();
            $table->foreign('admin_user_id')
                ->references('id')->on('admin_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_access_logs', function (Blueprint $table) {
            $table->dropForeign('admin_access_logs_admin_user_id_foreign');
        });
    }
}
