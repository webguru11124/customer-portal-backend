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
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'remember_token',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('external_id', 128)
                ->after('last_name')
                ->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
            $table->dropColumn(['external_id', 'last_name']);
            $table->dateTime('email_verified_at')->nullable()->after('email');
            $table->string('password')->after('email_verified_at');
            $table->string('remember_token', 100)->nullable()->after('password');
        });
    }
};
