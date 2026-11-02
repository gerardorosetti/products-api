<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->text('description');
            $table->decimal('price', 19, 4);
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('tax_cost', 19, 4);
            $table->decimal('manufacturing_cost', 19, 4);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
