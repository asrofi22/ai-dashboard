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
        Schema::create('dim_customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_name');
            $table->string('email')->unique()->nullable();
            $table->string('country');
            $table->date('signup_date');
            $table->timestamps();
        });

        Schema::create('dim_product', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->string('category');
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });

        Schema::create('fact_sales', function (Blueprint $table) {
            $table->id('sales_id');
            $table->foreignId('customer_id')->constrained('dim_customer', 'customer_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('dim_product', 'product_id')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('amount', 12, 2);
            $table->date('sales_date');
            $table->timestamps();
        });

        Schema::create('fact_payment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('sales_id')->constrained('fact_sales', 'sales_id')->onDelete('cascade');
            $table->string('payment_method');
            $table->string('payment_status'); // Success, Failed
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fact_payment');
        Schema::dropIfExists('fact_sales');
        Schema::dropIfExists('dim_product');
        Schema::dropIfExists('dim_customer');
    }
};
