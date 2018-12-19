<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJetRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jet_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('amount')->nullable();
            $table->string('fee')->nullable();
            $table->integer('request_owner_id')->nullable();
            $table->string('request_owner_wallet_address')->nullable();
            $table->integer('partner_id')->nullable();
            $table->string('partner_wallet_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jet_requests');
    }
}
