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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('IsVerified')->default(false);
            $table->string('Code')->nullable();
            $table->timestamp('CodeExpiresAt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('IsVerified');
            $table->dropColumn('Code');
            $table->dropColumn('CodeExpiresAt');
        });
    }
};
