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
        Schema::create('social_actions', function (Blueprint $table) {
            $approvalStatus = config('data.approval');
            $table->id();
            $table->uuid("user_id");
            $table->string('proof_img', 200);
            $table->enum('status', array_values($approvalStatus))->default(object($approvalStatus)->pending);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_actions');
    }
};
