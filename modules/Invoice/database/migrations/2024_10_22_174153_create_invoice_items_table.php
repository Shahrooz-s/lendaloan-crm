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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 3);
            $table->decimal('total', 15, 3);
            $table->decimal('taxable_amount', 15, 3);
            $table->decimal('tax_rate', 15, 3)->default(0);
            $table->decimal('tax_total', 15, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
