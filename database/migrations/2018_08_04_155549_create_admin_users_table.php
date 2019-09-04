<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->string('first_name',48)->nullable()->charset('utf8mb4')->collate('unicode_ci');
                $table->string('last_name',48)->nullable()->charset('utf8mb4')->collate('unicode_ci');
                $table->string('email',64)->charset('utf8mb4')->collate('unicode_ci')->unique();
                $table->string('password')->nullable()->charset('utf8mb4')->collate('unicode_ci');
                $table->dateTime('last_login_at')->nullable();
                $table->unsignedTinyInteger('is_web_admin')->nullable();
                $table->unsignedTinyInteger('is_fan_app_admin')->nullable();
                $table->text('google2fa_secret')->nullable()->charset('utf8mb4')->collate('unicode_ci');
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
        Schema::dropIfExists('admin_users');
    }
}
