<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Banking\Enums\Wallet\WalletStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('ownable');
            $table->string('currency');
            $table->string('status')->comment(WalletStatus::stringifyCases());
            $table->unsignedBigInteger('balance')
                ->default(0)
                ->comment('Only touched through transactions');

            $table->unsignedBigInteger('available_earnings')
                ->default(0)
                ->comment('Only touched through transactions');

            $table->unsignedBigInteger('total_earnings')
                ->default(0)
                ->comment('Only touched through transactions');

            $table->unsignedBigInteger('total_withdrawn')
                ->default(0)
                ->comment('Only touched through transactions');

            $table->unsignedBigInteger('total_invested')
                ->default(0)
                ->comment('Only touched through transactions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
