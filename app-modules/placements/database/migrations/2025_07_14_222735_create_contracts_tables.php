<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Placements\Enums\Contracts\ContractStatusEnum;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('template_type')->comment(ContractTemplateTypeEnum::stringifyCases());
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contract_template_id')->constrained('contract_templates')->cascadeOnDelete();
            $table->string('status')->comment(ContractStatusEnum::stringifyCases());
            $table->string('external_id')->nullable()->comment('Identifier on the e-signature provider');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('placements_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('placement_id')->constrained('user_placements')->cascadeOnDelete();
            $table->foreignUuid('contract_id')->constrained('contracts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placements_contracts');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
    }
};
