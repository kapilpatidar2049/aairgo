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
        if(Schema::hasTable('requests')) {
            if(!Schema::hasColumn('requests','shared_ride')) {
                Schema::table('requests', function (Blueprint $table) {
                    $table->boolean('shared_ride')->after('is_bid_ride')->default(false);
                });
            }

            if(!Schema::hasColumn('requests','seats_taken')) {
                Schema::table('requests', function (Blueprint $table) {
                    $table->Integer('seats_taken')->after('shared_ride')->default(false);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasTable('requests')) {
            if(Schema::hasColumn('requests','shared_ride')) {
                Schema::table('requests', function (Blueprint $table) {
                    $table->dropColumn('shared_ride');
                });
            }
            if(Schema::hasColumn('requests','seats_taken')) {
                Schema::table('requests', function (Blueprint $table) {
                    $table->dropColumn('seats_taken');
                });
            }
        }
    }
};
