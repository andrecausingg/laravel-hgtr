<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_info_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Add user_id as a foreign key
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('contact_num')->nullable();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('region_code')->nullable();
            $table->string('province_code')->nullable();
            $table->string('city_or_municipality_code')->nullable();
            $table->string('region_name')->nullable();
            $table->string('province_name')->nullable();
            $table->string('city_or_municipality_name')->nullable(); 
            $table->string('barangay')->nullable();
            $table->text('description_location')->nullable();
            $table->timestamps();

            // Define foreign key constraint
            $table->foreign('user_id')->references('id')->on('users_tbl')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_info_tbl');
    }
}


?>