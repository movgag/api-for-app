<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUsersTable1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('one_time_token')->after('password')->nullable();
            $table->integer('status')->after('password')
                ->comment('0-unverified , 1-pin seted and verified, 2-user created, 3-mail code, 4-qr code verify')
                ->default(0);
            $table->string('qr_path')->after('password')->nullable();
            $table->string('qr_code')->after('password')->nullable();
            $table->string('mail_code')->after('password')->nullable();
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
            $table->dropColumn('one_time_token');
            $table->dropColumn('status');
            $table->dropColumn('qr_path');
            $table->dropColumn('qr_code');
            $table->dropColumn('mail_code');
        });
    }
}
