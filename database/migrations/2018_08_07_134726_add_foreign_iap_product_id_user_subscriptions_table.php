<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignIapProductIdUserSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('user_subscriptions', function (Blueprint $table) {
            // $table->integer('iap_product_id')->unsigned()->change();
            // $table->foreign('iap_product_id')
            //     ->references('id')->on('iap_products')
            //     ->onDelete('cascade');
        });

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // $table->dropForeign('user_subscriptions_iap_product_id_foreign');
        });
    }
}
