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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('IdCartItem', 5)->primary();
            $table->char('IdCart', 5);
            $table->char('IdProduct', 3);
            $table->tinyInteger('Quantity');

            $table->foreign('IdCart')
                ->references('IdCart')
                ->on('carts')
                ->onDelete('cascade');

            $table->foreign('IdProduct')
                ->references('IdProduct')
                ->on('products')
                ->onDelete('cascade');
                    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
