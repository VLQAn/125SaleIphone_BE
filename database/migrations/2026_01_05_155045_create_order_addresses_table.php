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
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->engine('InnoDB');

            $table->char('IdOrderAdd', 5)->primary();
            $table->char('IdOrder', 5);
            $table->string('FullName', 255)->nullable();
            $table->char('Phone', 10)->nullable();
            $table->string('Address', 255)->nullable();

            $table->foreign('IdOrder')
                ->references('IdOrder')
                ->on('Orders')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
