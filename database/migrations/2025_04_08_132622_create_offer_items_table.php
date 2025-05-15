<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('offer_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('product_service_id')->nullable();
            $table->foreignId('offer_id')->nullable()->constrained('offers', 'offer_id')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->string('unit_code', 50)->nullable();
            $table->string('tax_rate')->nullable();
            $table->decimal('discount_value', 10, 2) ->default(0);
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_items');
    }
};
