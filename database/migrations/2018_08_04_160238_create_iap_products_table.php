<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIapProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('iap_products')) {
            Schema::create('iap_products', function (Blueprint $table) {
                $table->increments('id');
                $table->string('key',32)->charset('latin1')->collate('swedish_ci');
                $table->string('product_id',32)->charset('latin1')->collate('swedish_ci');
                $table->string('text',64)->charset('latin1')->collate('swedish_ci')->nullable();
                $table->string('platform',8)->charset('latin1')->collate('swedish_ci')->nullable();
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
        Schema::dropIfExists('iap_products');
    }
}
