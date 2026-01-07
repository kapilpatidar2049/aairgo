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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('referrer_id'); // user who referred
            $table->unsignedInteger('referred_user_id'); // the new user who used the code
            $table->string('referral_type')->nullable(); // snapshot of settings at time of apply
            $table->decimal('referrer_amount', 12, 2)->default(0);
            $table->decimal('new_amount', 12, 2)->default(0);
            $table->enum('status', ['pending','credited','cancelled'])->default('pending');
            $table->json('meta')->nullable(); // optional extra info
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['referrer_id','referred_user_id']); // one referral per pair
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
