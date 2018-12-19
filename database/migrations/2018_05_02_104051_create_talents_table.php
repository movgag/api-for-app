<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTalentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('talents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->bigInteger('val')->nullable();
            $table->integer('percentage')->default(0);

            $table->bigInteger('jet_amount')->comment('depends on val and percentage')->default(0);
            $table->bigInteger('sold')->default(0);
            $table->bigInteger('available')->default(0);

            $table->string('jets')->nullable();
            $table->string('image_small')->nullable();
            $table->string('image_large')->nullable();
            $table->string('image_banner')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('is_active')->comment('0-not active, 1-active')->default(0);
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
        Schema::dropIfExists('talents');
    }
}
