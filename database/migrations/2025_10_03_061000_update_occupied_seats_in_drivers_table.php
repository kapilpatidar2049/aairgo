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
        if(Schema::hasTable('drivers')) {
            if(!Schema::hasColumn('drivers','occupied_seats')) {
                Schema::table('drivers', function (Blueprint $table) {
                    $table->integer('occupied_seats')->after('reason')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasTable('drivers')) {
            if(Schema::hasColumn('drivers','occupied_seats')) {
                Schema::table('drivers', function (Blueprint $table) {
                    $table->dropColumn('occupied_seats');
                });
            }
        }
    }
};
