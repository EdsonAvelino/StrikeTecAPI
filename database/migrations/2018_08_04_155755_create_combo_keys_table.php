<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('combo_keys')) {
            Schema::create('combo_keys', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedTinyInteger('combo_id');
                $table->char('punch_type_id', 2)->charset('latin1')->collate('swedish_ci');
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
        Schema::dropIfExists('combo_keys');
    }
}
