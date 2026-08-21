<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Wallet;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Ledger::class);
            $table->foreignIdFor(Wallet::class);
            $table->string('entry_type')->comment(TransactionEntryType::stringifyCases());
            $table->string('transaction_kind')->comment(TransactionKind::stringifyCases());
            $table->bigInteger('amount');
            $table->string('currency');
            $table->text('description');
            $table->string('reference')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('entry_at');
            $table->timestamps();

            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
