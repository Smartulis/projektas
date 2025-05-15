<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('total_with_vat', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('draft');
            $table->string('public_token', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
