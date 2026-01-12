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
        Schema::create('payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('IdPayment', 5)->primary();
            $table->char('IdOrder', 5);
            $table->char('IdPaymentMethod', 3);
            $table->string('StripePaymentId');
            $table->tinyInteger('Status')->default(0);// 0: đang thanh toán; 1: đã thanh toán; thanh toán thất bại
            $table->integer('Amount');

            $table->foreign('IdOrder')
                ->references('IdOrder')
                ->on('orders')
                ->onDelete('cascade');
            
            $table->foreign('IdPaymentMethod')
                ->references('IdPaymentMethod')
                ->on('payment_methods')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
