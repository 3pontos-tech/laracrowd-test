<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Placements\Enums\PlacementCompletedKind;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_placements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('offer_id')->constrained('startup_offers')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')
                ->comment(PlacementStatus::stringifyCases());
            $table->string('slug');
            $table->string('process')
                ->comment(PlacementProcess::stringifyCases());
            $table->boolean('automatic_withdrawal')->default(false);

            $table->bigInteger('grand_total_amount');
            $table->integer('shares_purchased')->nullable();

            $table->string('completion_reason')
                ->comment(PlacementCompletedKind::stringifyCases())
                ->nullable();
            $table->text('completion_notes')->nullable();

            $table->timestamp('placement_payment_at')->nullable();
            $table->timestamp('placement_starting_at')->nullable();
            $table->timestamp('placement_finished_at')->nullable();
            $table->timestamp('withdrawal_requested_at')->nullable();
            $table->boolean('total_withdrawn_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['user_id', 'status', 'placement_starting_at'],
                'user_placements_cvm_cap_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_placements');
    }
};
