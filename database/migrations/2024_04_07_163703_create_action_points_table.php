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
          $table->uuid("id")->primary();
            $table->foreignUuid('user_id');
            $table->integer('balance')->default(0);
            $table->integer('verified_tweets')->default(0);
            $table->timestamp('last_tweet')->nullable();
            $table->integer('referral_point')->default(0);
            $table->integer('points_per_day')->default(0);
            $table->integer('earning_point')->default(0);
            $table->timestamps();
        });

      Schema::table("action_points", function (Blueprint $table) {
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_points');
    }
};
