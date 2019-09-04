<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignCountryIdUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('users', function (Blueprint $table) {
        //     // $table->integer('country_id')->unsigned()->change();
        //     $table->foreign('country_id')
        //         ->references('id')->on('countries')
        //         ->onDelete('cascade');
        // });
    }

    /**
        * Reverse the migrations.
        *
        * @return void
        */
    public function down()
    {
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropForeign('users_country_id_foreign');
        // });
    }
}
