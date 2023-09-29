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
        Schema::create('orders_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('group_id');
            $table->string('order_id');
            $table->string('product_group_id')->nullable();

            $table->string('role')->nullable();
            $table->string('category');
            $table->string('name');
            $table->string('image');
            $table->string('size');
            $table->string('color');
            $table->integer('quantity');
            $table->integer('discount')->nullable();
            $table->text('description')->nullable();
            $table->decimal('product_price', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2);
            $table->decimal('final_total_price', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status');

            $table->string('reason_cancel')->nullable();

            $table->string('return_reason')->nullable();
            $table->string('return_image1')->nullable();
            $table->string('return_image2')->nullable();
            $table->string('return_image3')->nullable();
            $table->string('return_image4')->nullable();
            $table->text('return_description')->nullable();
            $table->text('return_solution')->nullable();

            $table->timestamp('return_shipping_at')->nullable();
            $table->timestamp('return_accept_at')->nullable();
            $table->timestamp('return_decline_at')->nullable();
            $table->timestamp('return_completed_at')->nullable();
            $table->timestamp('return_failed_at')->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->timestamp('cancel_at')->nullable();
            $table->timestamp('order_receive_at')->nullable();

            $table->timestamp('mark_as_done_at')->nullable();
            $table->timestamp('ship_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('return_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_tbl');
    }
};
