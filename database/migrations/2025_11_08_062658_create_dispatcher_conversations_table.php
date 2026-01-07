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
        Schema::create('dispatcher_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Setting UUID as primary key
            $table->unsignedInteger('user_id')->nullable();
            $table->uuid('dispatcher_id')->nullable(); // Use BigInteger for dispatcher IDs
            $table->string('subject')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('dispatcher_id')
                  ->references('id')
                  ->on('admin_details')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatcher_conversations');
    }
};
