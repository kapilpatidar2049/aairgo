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
            if (!Schema::hasColumn('zone_types', 'order_number')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->unsignedInteger('order_number')->after('transport_type')->default(1);
                });
            }else{
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->unsignedInteger('order_number')->after('transport_type')->default(1)->change();
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
            if (Schema::hasColumn('zone_types', 'order_number')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->dropColumn('order_number');
                });
            }
        }
    }
};
