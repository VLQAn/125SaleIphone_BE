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
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('IdUser', 5)->primary();
            $table->string('UserName');
            $table->string('Email')->unique();
            $table->string('Password');
            $table->string('Phone')->nullable();
            $table->string('Address')->nullable();
            $table->string('Provider')->nullable();
            $table->string('ProviderId')->nullable();
            $table->char('Role', 2);
            $table->timestamp('EmailVerifyAt')->nullable();

            $table->foreign('Role')
                ->references('IdRole')
                ->on('roles')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
