<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('transaction_setups', function (Blueprint $table) {
            $table->index(['account_number', 'transaction_setup_id'], 'K_account_number_transaction_setup_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('transaction_setups', function (Blueprint $table) {
            $table->dropIndex('K_account_number_transaction_setup_id');
        });
    }
};
