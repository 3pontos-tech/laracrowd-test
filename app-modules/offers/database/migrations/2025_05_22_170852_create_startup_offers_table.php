<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Offers\Enums\OfferRiskEnum;
use Platform\Offers\Enums\OfferStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_offers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('startup_id')->constrained('startup')->cascadeOnDelete();
            $table->string('reference')->unique()->comment('Unique reference for the offer (e.g., "O1001")');
            $table->string('modality_type')->comment(OfferModalityEnum::stringifyCases());
            $table->string('status')->comment(OfferStatusEnum::stringifyCases());
            $table->string('name');
            $table->string('slug');
            $table->boolean('active')->default(false);
            $table->boolean('visible')->default(false);
            $table->boolean('visible_on_logged')->default(false);
            $table->boolean('visible_for_investor')->default(false);
            $table->string('remuneration')->nullable();
            $table->string('amortization')->nullable();
            $table->string('application_quotation')->nullable();
            $table->string('liquidation_event')->nullable();
            $table->timestamp('capturing_until');
            $table->timestamp('finish_at')->nullable();
            $table->string('risk_level')->comment(OfferRiskEnum::stringifyCases());
            $table->float('roi');
            $table->integer('duration_in_months');

            $table->bigInteger(column: 'total_value');
            $table->bigInteger(column: 'min_investment');

            $table->integer('min_shares_count')->nullable()->comment('Minimum number of shares to be purchased if the modality is shares-based');
            $table->integer('total_shares_count')->nullable()->comment('Total number of shares available if the modality is shares-based');

            $table->integer('withdraw_percentage_limit');
            $table->string('withdraw_periodicity');
            $table->integer('withdraw_grace_period_in_months');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_offers');
    }
};
