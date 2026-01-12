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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->engine('InnoDB');

            $table->char('IdPaymentMethod', 3)->primary();
            $table->string('MethodName', 50);
            $table->string('Decription', 255)->nullable();
            $table->tinyInteger('Status')->default(1);// 1: hoạt động, 0 ngưng dịch vụ

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
