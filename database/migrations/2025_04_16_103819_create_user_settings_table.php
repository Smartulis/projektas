<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('currency')->default('EUR');
            $table->string('language', 2)->default('en');
            $table->unsignedInteger('default_valid_until')->default(14);
            $table->unsignedInteger('default_due_date')->default(14);
            $table->text('payment_terms')->nullable();
            $table->json('tax_rates')->nullable();
            $table->string('default_tax_rate')->default('0');
            $table->string('estimate_prefix')->default('EST');
            $table->string('estimate_number_format')->default('{prefix}-{date}-{counter}');
            $table->integer('estimate_counter')->default(1);
            $table->string('invoice_prefix')->default('INV');
            $table->string('invoice_number_format')->default('{prefix}-{counter}');
            $table->unsignedInteger('invoice_counter')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
