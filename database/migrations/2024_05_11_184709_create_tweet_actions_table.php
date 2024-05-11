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
        Schema::create('tweet_actions', function (Blueprint $table) {
            $approvalStatus = config("data.approvalStatus");
            $table->uuid("id")->primary();
            $table->uuid("user_id");
            $table->longText('tweet_link');
            $table->enum('status', array_values($approvalStatus))->default(object($approvalStatus)->pending);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tweet_actions');
    }
};
