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
        Schema::create('order_items', function (Blueprint $table) {
            // IdOrderItem là string, primary key
            $table->string('IdOrderItem', 10)->primary();

            $table->string('IdOrder');
            $table->string('IdProduct');
            $table->integer('Quantity');
            $table->decimal('UnitPrice', 10, 2); // giá từng sản phẩm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
