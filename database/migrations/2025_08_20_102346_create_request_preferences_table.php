<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('request_id');
            $table->unsignedInteger('preference_price_id');
            $table->double('price', 10, 2)->default(0);
            $table->timestamps();


            $table->foreign('preference_price_id')
                    ->references('id')
                    ->on('preference_prices')
                    ->onDelete('cascade');

            $table->foreign('request_id')
                    ->references('id')
                    ->on('requests')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_preferences');
    }
};
