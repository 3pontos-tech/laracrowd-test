<?php

declare(strict_types=1);

namespace Platform\Placements\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Models\Placement;

class ProcessWithdrawalsCommand extends Command
{
    protected $signature = 'placements:process-withdrawals';

    protected $description = 'Finalizes withdrawal requests after the regulated refund period.';

    public function handle(): int
    {
        $cutoffDate = $this->subtractBusinessDays(now(), 7);

        $placements = Placement::query()
            ->where('status', PlacementStatus::Withdrawing)
            ->where('withdrawal_requested_at', '<=', $cutoffDate)
            ->get();

        if ($placements->isEmpty()) {
            $this->info('No withdrawals to process.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Processing %d withdrawal(s)...', $placements->count()));

        foreach ($placements as $placement) {
            $placement->update([
                'status' => PlacementStatus::Cancelled,
                'process' => PlacementProcess::Cancelled,
            ]);

            $placement->wallet()->update(['status' => WalletStatus::EndOfLife]);

            $originalContract = $placement->contract()
                ->whereHas('template', fn ($q) => $q->where('template_type', ContractTemplateTypeEnum::Contract))
                ->first();

            $originalContract?->markAsCancelled();

            $this->line(sprintf('  ✓ Placement %s cancelled.', $placement->slug));
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Calculate the date that is N business days before the given date.
     * Business days exclude Saturday and Sunday.
     */
    private function subtractBusinessDays(Carbon $from, int $days): Carbon
    {
        $date = $from->copy();
        $remaining = $days;

        while ($remaining > 0) {
            $date->subDay();

            if (! $date->isWeekend()) {
                $remaining--;
            }
        }

        return $date->endOfDay();
    }
}
