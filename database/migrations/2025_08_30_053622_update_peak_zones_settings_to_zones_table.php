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
        if (Schema::hasTable('zones')) {
            if (!Schema::hasColumn('zones', 'peak_zone_radius')) {
                Schema::table('zones', function (Blueprint $table) {
                    $table->integer('peak_zone_radius')->after('default_vehicle_type_for_delivery')->default(0)->nullable();
                });
            }
        }
        if (Schema::hasTable('zones')) {
            if (!Schema::hasColumn('zones', 'peak_zone_duration')) {
                Schema::table('zones', function (Blueprint $table) {
                    $table->double('peak_zone_duration', 10, 2)->after('peak_zone_radius')->default(0)->nullable();
                });
            }
        }
        if (Schema::hasTable('zones')) {
            if (!Schema::hasColumn('zones', 'peak_zone_history_duration')) {
                Schema::table('zones', function (Blueprint $table) {
                    $table->double('peak_zone_history_duration', 10, 2)->after('peak_zone_duration')->default(0)->nullable();
                });
            }
        }
        if (Schema::hasTable('zones')) {
            if (!Schema::hasColumn('zones', 'peak_zone_ride_count')) {
                Schema::table('zones', function (Blueprint $table) {
                    $table->integer('peak_zone_ride_count')->after('peak_zone_history_duration')->default(0)->nullable();
                });
            }
        }
        if (Schema::hasTable('zones')) {
            if (!Schema::hasColumn('zones', 'distance_price_percentage')) {
                Schema::table('zones', function (Blueprint $table) {
                    $table->integer('distance_price_percentage')->after('peak_zone_ride_count')->default(0)->comment('In percentage')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            //
        });
    }
};
