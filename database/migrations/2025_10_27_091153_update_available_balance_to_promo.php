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
         if(Schema::hasTable('promo')) {
            if(!Schema::hasColumn('promo','available_balance')) {
                Schema::table('promo', function (Blueprint $table) {
                    $table->integer('available_balance')->after('cummulative_maximum_discount_amount')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo', function (Blueprint $table) {
            //
        });
    }
};
