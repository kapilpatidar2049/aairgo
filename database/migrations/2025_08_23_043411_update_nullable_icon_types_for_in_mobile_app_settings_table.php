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
        if(Schema::hasTable('mobile_app_settings')){
            if(Schema::hasColumn('mobile_app_settings','icon_types_for')){
                Schema::table('mobile_app_settings', function (Blueprint $table) {
                    $table->string('icon_types_for')->nullable(true)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasTable('mobile_app_settings')){
            if(Schema::hasColumn('mobile_app_settings','icon_types_for')){
                Schema::table('mobile_app_settings', function (Blueprint $table) {
                    $table->string('icon_types_for')->nullable(false)->change();
                });
            }
        }
    }
};
