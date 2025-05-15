<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id('offer_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('estimate_number')->nullable();
            $table->enum('status', ['created', 'sent', 'accepted', 'rejected', 'viewed', 'converted'])->default('created');
            $table->date('date')->default(now());
            $table->date('valid_until');
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total_with_vat', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->text('customer_comment')->nullable();
            $table->text('notes')->nullable();
            $table->string('public_token', 64)->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offers');
    }
};