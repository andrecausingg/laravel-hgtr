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
        Schema::create('product_tbl', function (Blueprint $table) {
            $table->id();
            $table->string('group_id');
            $table->string('role')->nullable();
            $table->string('image');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->string('category');
            $table->string('color');
            $table->string('size');
            $table->integer('discount')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tbl');
    }
};
