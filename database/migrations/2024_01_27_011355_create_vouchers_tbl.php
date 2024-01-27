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
        Schema::create('vouchers_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->varchar('name', 255);
            $table->varchar('status', 255);
            $table->integer('discount');
            $table->timestamp('activate_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers_tbl');
    }
};
