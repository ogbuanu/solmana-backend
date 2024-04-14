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
        Schema::create('token_verifications', function (Blueprint $table) {
           $table->uuid("id")->primary();
            $table->string('email');
            $table->string('token_for');
            $table->enum('status',['USED','NOTUSED'])->default('NOTUSED');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_verifications');
    }
};
