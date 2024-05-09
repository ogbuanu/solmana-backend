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
        Schema::create('action_points', function (Blueprint $table) {
            $tireLevel = config("data.tireLevel");

            $table->uuid("id")->primary();
            $table->foreignUuid('user_id');
            $table->integer('balance')->default(0);
            $table->integer('verified_tweets')->default(0);
            $table->string('last_tweet')->nullable();
            $table->string('last_kyc_earning')->nullable();
            $table->string('last_referral')->nullable();
            $table->longText('wallet_address')->nullable();
            $table->enum('tire_level', array_values($tireLevel))->default(object($tireLevel)->none);
            $table->timestamps();
        });

        Schema::table("action_points", function (Blueprint $table) {
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });
    }

    // TIRE
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_points');
    }
};
