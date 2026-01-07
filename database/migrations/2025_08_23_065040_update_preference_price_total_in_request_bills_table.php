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
        if (Schema::hasTable('request_bills')) {
            if (!Schema::hasColumn('request_bills', 'preference_price_total')) {
                Schema::table('request_bills', function (Blueprint $table) {
                    $table->double('preference_price_total', 10, 2)->after('additional_charges_amount')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('request_bills')) {
            if (!Schema::hasColumn('request_bills', 'preference_price_total')) {
                Schema::table('request_bills', function (Blueprint $table) {
                    $table->string('preference_price_total')->after('requested_currency_code')->nullable();
                });
            }
        }
    }
};
