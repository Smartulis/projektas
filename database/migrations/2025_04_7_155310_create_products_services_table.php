<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('products_services', function (Blueprint $table) {
            $table->bigIncrements('product_service_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_without_vat', 10, 2);
            $table->decimal('price_with_vat', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedBigInteger('unit_id');
            $table->integer('stock_quantity')->default(0);
            $table->string('sku', 50)->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->enum('status', ['Active', 'Expired', 'Not Available'])->default('Active');
            $table->foreign('unit_id')->references('id')->on('measurement_units')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products_services');
    }
};
