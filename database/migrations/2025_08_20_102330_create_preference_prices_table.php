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
        Schema::create('preference_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('zone_type_id');
            $table->unsignedInteger('preference_id');
            $table->double('price', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('preference_id')
                    ->references('id')
                    ->on('preferences')
                    ->onDelete('cascade');

            $table->foreign('zone_type_id')
                    ->references('id')
                    ->on('zone_types')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preference_prices');
    }
};
