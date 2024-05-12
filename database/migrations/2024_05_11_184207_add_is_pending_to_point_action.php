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
        Schema::table('action_points', function (Blueprint $table) {
            //
            $bool = config("data.boolean");

            $table->enum('is_pending', array_values($bool))->default(object($bool)->false)->after('tire_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('action_points', function (Blueprint $table) {
            //
        });
    }
};
