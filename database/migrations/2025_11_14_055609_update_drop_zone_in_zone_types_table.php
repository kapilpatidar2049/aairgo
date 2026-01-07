<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('zone_types')) {
            if (!Schema::hasColumn('zone_types', 'drop_zone')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->uuid('drop_zone')->after('transport_type')->nullable();
                    
                    $table->foreign('drop_zone')
                            ->references('id')
                            ->on('zones')
                            ->onDelete('cascade');
                });
            }
        } 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        if (Schema::hasTable('zone_types')) {
            if (Schema::hasColumn('zone_types', 'drop_zone')) {
                Schema::table('zone_types', function (Blueprint $table) {
                    $table->dropColumn('drop_zone');
                });
            }
        }
    }
};
