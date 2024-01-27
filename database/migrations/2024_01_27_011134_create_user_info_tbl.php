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
        Schema::create('user_info_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('contact_num')->nullable();
            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->string('region_code')->nullable();
            $table->string('province_code')->nullable();
            $table->string('city_or_municipality_code')->nullable();
            $table->text('region_name')->nullable();
            $table->text('province_name')->nullable();
            $table->text('city_or_municipality_name')->nullable();
            $table->text('barangay')->nullable();
            $table->text('description_location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_info_tbl');
    }
};
