<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Banking\Enums\Transactions\TransactionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->text('description');
            $table->string('reference')->nullable();
            $table->string('status')->comment(TransactionStatus::stringifyCases());
            $table->jsonb('metadata')->nullable();
            $table->timestamp('entry_at');
            $table->timestamps();

            $table->index('status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
