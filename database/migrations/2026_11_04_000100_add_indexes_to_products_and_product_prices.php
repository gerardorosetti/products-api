<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('currency_id');
            $table->index('price');
            $table->index('created_at');
        });

        Schema::table('product_prices', function (Blueprint $table): void {
            $table->index('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table): void {
            $table->dropIndex(['currency_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['price']);
            $table->dropIndex(['created_at']);
        });
    }
};
