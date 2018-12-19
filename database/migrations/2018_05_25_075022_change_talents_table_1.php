<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTalentsTable1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->string('country')->after('description')->nullable();
            $table->string('club')->after('description')->nullable();
            $table->string('position')->after('description')->nullable();
            $table->string('sport')->after('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropColumn('country');
            $table->dropColumn('club');
            $table->dropColumn('position');
            $table->dropColumn('sport');
        });
    }
}
