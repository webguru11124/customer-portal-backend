<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillingAddressToTransactionSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_setups', function (Blueprint $table) {
            $table->string('slug', 32)->after('id');
            $table->string('billing_name', 128)->nullable()->after('last_4');
            $table->string('billing_address_line_1', 128)->nullable()->after('billing_name');
            $table->string('billing_address_line_2', 128)->nullable()->after('billing_address_line_1');
            $table->string('billing_city', 64)->nullable()->after('billing_address_line_2');
            $table->string('billing_state', 2)->nullable()->after('billing_city');
            $table->string('billing_zip', 5)->nullable()->after('billing_state');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_setups', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('billing_name');
            $table->dropColumn('billing_address_line_1');
            $table->dropColumn('billing_address_line_2');
            $table->dropColumn('billing_city');
            $table->dropColumn('billing_state');
            $table->dropColumn('billing_zip');
            $table->dropSoftDeletes();
        });
    }
}
