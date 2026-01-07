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
        if (Schema::hasTable('zone_type_package_prices')) {
            
            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission_type')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->tinyInteger('admin_commission_type')->after('active')->nullable();
                });
            }

            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->double('admin_commission',10,2)->after('admin_commission_type')->default(0);
                });
            }

            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission_type_from_driver')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->tinyInteger('admin_commission_type_from_driver')->after('admin_commission')->nullable();
                });
            }

            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission_from_driver')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->double('admin_commission_from_driver',10,2)->after('admin_commission_type_from_driver')->default(0);
                });
            }
            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission_type_from_owner')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->tinyInteger('admin_commission_type_from_owner')->after('admin_commission_from_driver')->nullable();
                });
            }

            if (!Schema::hasColumn('zone_type_package_prices', 'admin_commission_from_owner')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->double('admin_commission_from_owner',10,2)->after('admin_commission_type_from_owner')->default(0);
                });
            }

            if (!Schema::hasColumn('zone_type_package_prices', 'service_tax')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->double('service_tax',10,2)->after('admin_commission_from_owner')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('zone_type_package_prices')) {
            
            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission_type')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->dropColumn('admin_commission_type');
                });
            }

            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->dropColumn('admin_commission');
                });
            }

            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission_type_from_driver')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->dropColumn('admin_commission_type_from_driver');
                });
            }

            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission_from_driver')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->dropColumn('admin_commission_from_driver');
                });
            }
            
            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission_type_from_owner')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->dropColumn('admin_commission_type_from_owner');
                });
            }

            if (Schema::hasColumn('zone_type_package_prices', 'admin_commission_from_owner')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                    $table->dropColumn('admin_commission_from_owner');
                });
            }

            if (Schema::hasColumn('zone_type_package_prices', 'service_tax')) {
                Schema::table('zone_type_package_prices', function (Blueprint $table) {
                  $table->dropColumn('service_tax');
                });
            }

        }
    }
};
