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
        Schema::create('users_tbl', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status');
            $table->string('ip_address');
            $table->integer('verification_num');
            $table->string('verification_key')->nullable();
            $table->string('session_verify_email')->nullable();
            $table->string('session_pass_reset')->nullable();
            $table->string('session_login')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('update_pass_reset_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_tbl');
    }
};
