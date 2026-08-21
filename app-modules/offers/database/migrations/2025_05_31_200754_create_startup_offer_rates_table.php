<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Offers\Models\Offer;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_offer_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Offer::class, 'startup_offer_id');
            $table->date('starting_at');
            $table->bigInteger('value');
            // Share rate aggregators
            $table->bigInteger('initial_bond_value')->nullable();
            $table->bigInteger('current_bond_value')->nullable();
            $table->bigInteger('residual_bond_value')->nullable();
            $table->boolean('can_pay')->default(false);
            // ----------------------

            $table->timestamps();

            $table->unique(['startup_offer_id', 'starting_at'], 'unique_rate_per_offer_per_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_offer_rates');
    }
};
