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
        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('IdProduct', 3)->primary();
            $table->char('IdCategory', 2);
            $table->string('NameProduct', 100);
            $table->integer('Price');
            $table->string('Decription', 255);
            $table->tinyInteger('Stock');

            $table->timestamps();

            $table->foreign('IdCategory')
                ->references('IdCategory')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
