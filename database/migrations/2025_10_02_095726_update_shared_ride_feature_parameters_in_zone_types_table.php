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
        if (Schema::hasTable('zone_types')) {
            
            if (!Schema::hasColumn('zone_types', 'enable_shared_ride')) {
                Schema::table('zone_types', function (Blueprint $table) {
                  $table->tinyInteger('enable_shared_ride')->after('payment_type')->default(false);
                });
            }

            if (!Schema::hasColumn('zone_types', 'price_per_seat')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->double('price_per_seat',10,2)->after('enable_shared_ride')->default(0)->nullable();
                });
            }

            if (!Schema::hasColumn('zone_types', 'shared_price_per_distance')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->double('shared_price_per_distance',10,2)->after('price_per_seat')->default(0)->nullable();
                });
            }

            if (!Schema::hasColumn('zone_types', 'shared_cancel_fee')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->double('shared_cancel_fee',10,2)->after('shared_price_per_distance')->default(0)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('zone_types')) {
            
            if (Schema::hasColumn('zone_types', 'enable_shared_ride')) {
                Schema::table('zone_types', function (Blueprint $table) {
                  $table->dropColumn('enable_shared_ride');
                });
            }

            if (Schema::hasColumn('zone_types', 'price_per_seat')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->dropColumn('price_per_seat');
                });
            }

            if (Schema::hasColumn('zone_types', 'shared_price_per_distance')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->dropColumn('shared_price_per_distance');
                });
            }

            if (Schema::hasColumn('zone_types', 'shared_cancel_fee')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->dropColumn('shared_cancel_fee');
                });
            }
        }
    }
};
