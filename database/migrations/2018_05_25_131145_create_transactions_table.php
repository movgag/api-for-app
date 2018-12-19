<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('case')->comment('transfer,talent, reward')->nullable();
            $table->string('amount')->nullable();
            $table->string('fee')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('talent_id')->nullable();
            $table->integer('reward_id')->nullable();
            $table->integer('sender_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->string('sender_wallet_address')->nullable();
            $table->string('partner_wallet_address')->nullable();
            $table->string('status')->comment('failed or success')->default('undefined');
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
