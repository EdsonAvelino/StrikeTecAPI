<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GetAgeFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('CREATE FUNCTION `get_age` (`birthday` DATE, `current_time_supplied` DATE)
			   RETURNS INT(3) 
			   RETURN DATEDIFF(current_time_supplied,birthday)/365;
		');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB:unprepared('DROP FUNCTION `get_age`; ');
    }
}
