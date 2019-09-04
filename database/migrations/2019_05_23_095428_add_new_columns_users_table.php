<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColumnsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('email',64)->nullable()->change();
            $table->boolean('is_coach')->after('password')->default(0);
            $table->boolean('is_client')->after('is_coach')->default(0);
            $table->integer('coach_user')->after('is_client')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('leaderboard', function (Blueprint $table) {
        //     $table->dropColumn('is_coach');
        //     $table->dropColumn('is_client');
        //     $table->dropColumn('coach_user');
        // });
    }
}
