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
        Schema::table('support_ticket_messages', function (Blueprint $table) {

            $foreignKeyExists = DB::select(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'support_ticket_messages' 
                AND COLUMN_NAME = 'user_id' 
                AND CONSTRAINT_SCHEMA = DATABASE()"
            );
            if($foreignKeyExists){
                $table->dropForeign(['user_id']);
            }

            // Make the column nullable

            // Re-add the foreign key constraint
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_ticket_messages', function (Blueprint $table) {
            $foreignKeyExists = DB::select(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'support_ticket_messages' 
                AND COLUMN_NAME = 'user_id' 
                AND CONSTRAINT_SCHEMA = DATABASE()"
            );
            if($foreignKeyExists){
                $table->dropForeign(['user_id']);
            }

            // Make the column nullable
            $table->unsignedInteger('user_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('user_id')->references('users_id')->on('support_tickets');
        });
    }
};
