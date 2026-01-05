<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NunoMaduro\Collision\Adapters\Phpunit\State;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('IdProductVar', 3)->primary();
            $table->char('IdProduct', 3);
            $table->string('Color', 50);
            $table->integer('Price');
            $table->string('ImgPath', 255)->nullable();
            $table->tinyInteger('Stock');

            $table->timestamps();

            $table->foreign('IdProduct')
                ->references('IdProduct')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
