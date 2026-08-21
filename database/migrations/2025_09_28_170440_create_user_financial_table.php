<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_financial', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('annual_gross_income', 18, 2)->nullable();
            $table->decimal('financial_investments_amount', 18, 2)->nullable();
            $table->boolean('is_qualified_investor')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_financial');
    }
};
