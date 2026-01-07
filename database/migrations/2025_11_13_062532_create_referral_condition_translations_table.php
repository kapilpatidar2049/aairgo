<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.new
     */
    public function up(): void
    {
        Schema::create('referral_condition_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('referral_id');
            $table->longText('description'); 
            $table->string('locale'); 
            $table->timestamps();

            $table->foreign('referral_id')
                    ->references('id')
                    ->on('referral_conditions')
                    ->onDelete('cascade');
        });
        if (Schema::hasTable('referral_conditions')) {
            if (!Schema::hasColumn('referral_conditions', 'translation_dataset')) {
                Schema::table('referral_conditions', function (Blueprint $table) {  
                    $table->text('translation_dataset')->after('description')->nullable(); 
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_condition_translations');
        if (Schema::hasTable('referral_conditions')) {
            if (Schema::hasColumn('referral_conditions', 'translation_dataset')) {
                Schema::table('referral_conditions', function (Blueprint $table) {  
                    $table->dropColumn('translation_dataset');
                });
            }
        }
    }
};
