<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Offers\Enums\OfferRiskEnum;
use Platform\Offers\Enums\OfferStatusEnum;
use Platform\Offers\Enums\WithdrawalsPeriodicityEnum;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;

/**
 * Builds a dataset with the shape and volume of a live platform: investors,
 * offers with monthly rates, placements across the lifecycle, wallets and the
 * ledger history that backs them.
 */
class DemoDataSeeder extends Seeder
{
    /** Monetary scale used across the platform (see config/platform.php). */
    private const SCALE = 100_000_000;

    private const INVESTORS = 900;

    private const OFFERS = 50;

    private const PLACEMENTS = 2_000;

    private const RATE_MONTHS = 18;

    public function run(): void
    {
        $this->command?->info('Seeding investors...');
        $userIds = $this->seedInvestors();

        $this->command?->info('Seeding offers and rates...');
        [$offerIds, $offers] = $this->seedOffers($userIds);

        $this->command?->info('Seeding placements and wallets...');
        [$placements, $wallets] = $this->seedPlacements($userIds, $offerIds, $offers);

        $this->command?->info('Seeding ledger history...');
        $this->seedLedgerHistory($placements, $wallets);

        $this->command?->info('Applying historical adjustments...');
        $this->applyManualBalanceAdjustments($wallets);
        $this->seedPreMigrationEntries();
        $this->seedReprocessedPeriods($placements, $wallets);
        $this->seedHighVolumeInvestor($userIds, $offerIds);

        $this->command?->info('Done.');
    }

    /** @return list<string> */
    private function seedInvestors(): array
    {
        $now = now();
        $ids = [];
        $rows = [];
        $profiles = [];
        $password = Hash::make('password');

        for ($i = 0; $i < self::INVESTORS; $i++) {
            $id = (string) Str::uuid7();
            $ids[] = $id;
            $rows[] = [
                'id' => $id,
                'name' => fake()->name(),
                'email' => sprintf('investor%d@example.test', $i),
                'password' => $password,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // ~8% high income, ~2% qualified: the rest falls under the flat annual cap
            $roll = $i % 50;
            $profiles[] = [
                'user_id' => $id,
                'annual_gross_income' => $roll < 4 ? fake()->randomFloat(2, 250_000, 900_000) : fake()->randomFloat(2, 30_000, 180_000),
                'financial_investments_amount' => fake()->randomFloat(2, 0, 150_000),
                'is_qualified_investor' => $roll === 49,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        foreach (array_chunk($profiles, 500) as $chunk) {
            DB::table('user_financial')->insert($chunk);
        }

        return $ids;
    }

    /**
     * @param  list<string>  $userIds
     * @return array{0: list<string>, 1: array<string, array<string, mixed>>}
     */
    private function seedOffers(array $userIds): array
    {
        $now = now();
        $startups = [];
        $offers = [];
        $rates = [];
        $offerIds = [];
        $offerMap = [];

        for ($i = 0; $i < self::OFFERS; $i++) {
            $startupId = (string) Str::uuid7();
            $company = fake()->company();
            $startups[] = [
                'id' => $startupId,
                'user_id' => $userIds[$i % count($userIds)],
                'name' => $company,
                'slug' => Str::slug($company).'-'.$i,
                'tax_id' => fake()->numerify('##.###.###/####-##'),
                'short_description' => fake()->text(120),
                'segment' => fake()->randomElement(['fintech', 'healthtech', 'agtech', 'retail', 'energy']),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $offerId = (string) Str::uuid7();
            $offerIds[] = $offerId;
            $name = fake()->words(2, true);
            $modality = $i % 4 === 0 ? OfferModalityEnum::CommercialPaper : OfferModalityEnum::Crowdfunding;
            $duration = fake()->randomElement([12, 18, 24, 36]);

            $offerMap[$offerId] = [
                'modality' => $modality,
                'duration' => $duration,
                'grace' => fake()->randomElement([0, 1, 3, 6]),
                'periodicity' => fake()->randomElement([
                    WithdrawalsPeriodicityEnum::Monthly,
                    WithdrawalsPeriodicityEnum::Quarterly,
                    WithdrawalsPeriodicityEnum::SemiAnnual,
                ]),
            ];

            $offers[] = [
                'id' => $offerId,
                'startup_id' => $startupId,
                'reference' => sprintf('O%04d', 1000 + $i),
                'modality_type' => $modality->value,
                'status' => OfferStatusEnum::Active->value,
                'name' => Str::title($name),
                'slug' => Str::slug($name).'-'.$i,
                'active' => true,
                'visible' => true,
                'visible_on_logged' => true,
                'visible_for_investor' => true,
                'capturing_until' => $now->copy()->addMonths(2),
                'finish_at' => $now->copy()->addMonths($duration),
                'risk_level' => fake()->randomElement(OfferRiskEnum::cases())->value,
                'roi' => fake()->randomFloat(2, 8, 26),
                'duration_in_months' => $duration,
                'total_value' => fake()->numberBetween(500_000, 8_000_000) * self::SCALE,
                'min_investment' => 1_000 * self::SCALE,
                'min_shares_count' => 1,
                'total_shares_count' => fake()->numberBetween(1_000, 20_000),
                'withdraw_percentage_limit' => 100,
                'withdraw_periodicity' => $offerMap[$offerId]['periodicity']->value,
                'withdraw_grace_period_in_months' => $offerMap[$offerId]['grace'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            for ($m = self::RATE_MONTHS; $m >= 0; $m--) {
                $rates[] = [
                    'startup_offer_id' => $offerId,
                    'starting_at' => $now->copy()->subMonths($m)->startOfMonth()->toDateString(),
                    'value' => (int) round(fake()->randomFloat(4, 0.6, 1.9) * self::SCALE),
                    'can_pay' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('startup')->insert($startups);
        DB::table('startup_offers')->insert($offers);
        foreach (array_chunk($rates, 500) as $chunk) {
            DB::table('startup_offer_rates')->insert($chunk);
        }

        return [$offerIds, $offerMap];
    }

    /**
     * @param  list<string>  $userIds
     * @param  list<string>  $offerIds
     * @param  array<string, array<string, mixed>>  $offers
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function seedPlacements(array $userIds, array $offerIds, array $offers): array
    {
        $now = now();
        $rows = [];
        $wallets = [];
        $active = [];

        // Lifecycle spread: most placements are active, the rest sit in earlier
        // stages or were cancelled along the way.
        $statuses = array_merge(
            array_fill(0, 62, [PlacementStatus::Active, PlacementProcess::Approved]),
            array_fill(0, 12, [PlacementStatus::Finished, PlacementProcess::Approved]),
            array_fill(0, 8, [PlacementStatus::Draft, PlacementProcess::Draft]),
            array_fill(0, 6, [PlacementStatus::Contract, PlacementProcess::Reserved]),
            array_fill(0, 5, [PlacementStatus::Payment, PlacementProcess::Reserved]),
            array_fill(0, 4, [PlacementStatus::Cancelled, PlacementProcess::Cancelled]),
            array_fill(0, 2, [PlacementStatus::Withdrawing, PlacementProcess::Approved]),
            array_fill(0, 1, [PlacementStatus::WithdrawalCompleted, PlacementProcess::Cancelled]),
        );

        for ($i = 0; $i < self::PLACEMENTS; $i++) {
            $placementId = (string) Str::uuid7();
            $offerId = $offerIds[$i % count($offerIds)];
            [$status, $process] = $statuses[$i % count($statuses)];
            $amount = fake()->randomElement([1_000, 2_500, 5_000, 7_500, 10_000, 15_000]) * self::SCALE;
            $paidAt = $now->copy()->subMonths(fake()->numberBetween(1, self::RATE_MONTHS));
            $startingAt = $paidAt->copy()->addDays(5);

            $rows[] = [
                'id' => $placementId,
                'offer_id' => $offerId,
                'user_id' => $userIds[$i % count($userIds)],
                'status' => $status->value,
                'process' => $process->value,
                'slug' => sprintf('AP%06d', $i + 1),
                'automatic_withdrawal' => $i % 7 === 0,
                'grand_total_amount' => $amount,
                'shares_purchased' => (int) ($amount / (100 * self::SCALE)),
                'placement_payment_at' => $paidAt,
                'placement_starting_at' => $startingAt,
                'placement_finished_at' => $startingAt->copy()->addMonths($offers[$offerId]['duration']),
                'total_withdrawn_enabled' => true,
                'created_at' => $paidAt,
                'updated_at' => $now,
            ];

            if (! in_array($status, [PlacementStatus::Active, PlacementStatus::Finished], true)) {
                continue;
            }

            $walletId = (string) Str::uuid7();
            $wallets[] = [
                'id' => $walletId,
                'ownable_type' => 'placements',
                'ownable_id' => $placementId,
                'currency' => 'BRL',
                'status' => ($status === PlacementStatus::Finished ? WalletStatus::EndOfLife : WalletStatus::Active)->value,
                'balance' => $amount,
                'available_earnings' => 0,
                'total_earnings' => 0,
                'total_withdrawn' => 0,
                'total_invested' => $amount,
                'created_at' => $paidAt,
                'updated_at' => $now,
            ];
            $active[] = [
                'placement_id' => $placementId,
                'wallet_id' => $walletId,
                'slug' => sprintf('AP%06d', $i + 1),
                'amount' => $amount,
                'starting_at' => $startingAt,
                'offer_id' => $offerId,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_placements')->insert($chunk);
        }
        foreach (array_chunk($wallets, 500) as $chunk) {
            DB::table('wallets')->insert($chunk);
        }

        return [$active, $wallets];
    }

    /**
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $wallets
     */
    private function seedLedgerHistory(array $placements, array $wallets): void
    {
        $now = now();
        $walletBalances = [];
        foreach ($wallets as $wallet) {
            $walletBalances[$wallet['id']] = [
                'balance' => (int) $wallet['balance'],
                'earnings' => 0,
                'available' => 0,
            ];
        }

        $ledgerRows = [];
        $entryRows = [];
        $ledgerId = 1;

        $flush = function () use (&$ledgerRows, &$entryRows): void {
            if ($ledgerRows !== []) {
                DB::table('ledgers')->insert($ledgerRows);
                $ledgerRows = [];
            }

            if ($entryRows !== []) {
                DB::table('ledger_entries')->insert($entryRows);
                $entryRows = [];
            }
        };

        foreach ($placements as $placement) {
            // Opening investment credit
            $ledgerRows[] = [
                'id' => $ledgerId,
                'type' => TransactionKind::Investment->value,
                'description' => 'Wallet created for placement',
                'reference' => null,
                'status' => TransactionStatus::Completed->value,
                'metadata' => null,
                'entry_at' => $placement['starting_at'],
                'created_at' => $placement['starting_at'],
                'updated_at' => $placement['starting_at'],
            ];
            $entryRows[] = $this->entry($ledgerId, $placement['wallet_id'], TransactionEntryType::Credit,
                TransactionKind::Investment, (int) $placement['amount'], 'Wallet created for placement',
                null, $placement['starting_at']);
            $ledgerId++;

            // Monthly earnings from the placement start until now
            $cursor = $placement['starting_at']->copy()->startOfMonth()->addMonth();
            while ($cursor->lt($now)) {
                $period = $cursor->format('Y-m');
                $base = $walletBalances[$placement['wallet_id']]['balance'];
                $earning = (int) round($base * fake()->randomFloat(6, 0.006, 0.019));

                if ($earning <= 0) {
                    $cursor->addMonth();

                    continue;
                }

                $reference = sprintf('earning-%s-%s', $period, $placement['slug']);
                $ledgerRows[] = [
                    'id' => $ledgerId,
                    'type' => TransactionKind::Earning->value,
                    'description' => 'Monthly earnings for period '.$period,
                    'reference' => $reference,
                    'status' => TransactionStatus::Completed->value,
                    'metadata' => json_encode(['period' => $period, 'source' => 'monthly-rate']),
                    'entry_at' => $cursor->copy(),
                    'created_at' => $cursor->copy(),
                    'updated_at' => $cursor->copy(),
                ];
                $entryRows[] = $this->entry($ledgerId, $placement['wallet_id'], TransactionEntryType::Credit,
                    TransactionKind::Earning, $earning, 'Monthly earnings for period '.$period,
                    $reference, $cursor->copy());
                $ledgerId++;

                $walletBalances[$placement['wallet_id']]['balance'] += $earning;
                $walletBalances[$placement['wallet_id']]['earnings'] += $earning;
                $walletBalances[$placement['wallet_id']]['available'] += $earning;
                $cursor->addMonth();
            }

            if (count($entryRows) >= 2_000) {
                $flush();
            }
        }

        $flush();

        $this->resetLedgerSequence();

        DB::transaction(function () use ($walletBalances): void {
            foreach ($walletBalances as $walletId => $totals) {
                DB::table('wallets')->where('id', $walletId)->update([
                    'balance' => $totals['balance'],
                    'total_earnings' => $totals['earnings'],
                    'available_earnings' => $totals['available'],
                ]);
            }
        });
    }

    /**
     * Keeps the auto-increment in sync after explicit id inserts.
     */
    private function resetLedgerSequence(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT setval('ledgers_id_seq', (SELECT MAX(id) FROM ledgers))");
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(
        int $ledgerId,
        string $walletId,
        TransactionEntryType $type,
        TransactionKind $kind,
        int $amount,
        string $description,
        ?string $reference,
        \DateTimeInterface $at,
    ): array {
        return [
            'id' => (string) Str::uuid7(),
            'ledger_id' => $ledgerId,
            'wallet_id' => $walletId,
            'entry_type' => $type->value,
            'transaction_kind' => $kind->value,
            'amount' => $amount,
            'currency' => 'BRL',
            'description' => $description,
            'reference' => $reference,
            'metadata' => null,
            'entry_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ];
    }

    /**
     * Balance corrections applied directly by the operations team over time.
     *
     * @param  list<array<string, mixed>>  $wallets
     */
    private function applyManualBalanceAdjustments(array $wallets): void
    {
        $sample = array_slice($wallets, 0, 15);

        foreach ($sample as $index => $wallet) {
            $drift = (int) round(($index + 1) * 0.37 * self::SCALE);

            DB::table('wallets')
                ->where('id', $wallet['id'])
                ->update([
                    'balance' => DB::raw(sprintf('balance %s %d', $index % 2 === 0 ? '+' : '-', $drift)),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Movements carried over from the spreadsheet-based system that preceded the
     * current wallets.
     */
    private function seedPreMigrationEntries(): void
    {
        $now = now();
        $ledgerRows = [];
        $entryRows = [];
        $nextId = (int) DB::table('ledgers')->max('id') + 1;

        for ($i = 0; $i < 10; $i++) {
            $legacyWalletId = (string) Str::uuid7();
            $at = $now->copy()->subMonths(fake()->numberBetween(20, 30));

            $ledgerRows[] = [
                'id' => $nextId,
                'type' => TransactionKind::Investment->value,
                'description' => 'Imported movement',
                'reference' => sprintf('import-%04d', $i),
                'status' => TransactionStatus::Completed->value,
                'metadata' => json_encode(['source' => 'legacy-import']),
                'entry_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ];
            $entryRows[] = $this->entry($nextId, $legacyWalletId, TransactionEntryType::Credit,
                TransactionKind::Investment, fake()->numberBetween(1_000, 9_000) * self::SCALE,
                'Imported movement', sprintf('import-%04d', $i), $at);
            $nextId++;
        }

        DB::table('ledgers')->insert($ledgerRows);
        DB::table('ledger_entries')->insert($entryRows);
        $this->resetLedgerSequence();
    }

    /**
     * Periods that had to be run a second time after the monthly close was
     * interrupted.
     *
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $wallets
     */
    private function seedReprocessedPeriods(array $placements, array $wallets): void
    {
        $sample = array_slice($placements, 40, 5);
        $nextId = (int) DB::table('ledgers')->max('id') + 1;
        $ledgerRows = [];
        $entryRows = [];

        foreach ($sample as $placement) {
            $existing = DB::table('ledger_entries')
                ->where('wallet_id', $placement['wallet_id'])
                ->where('transaction_kind', TransactionKind::Earning->value)
                ->orderByDesc('entry_at')
                ->first();

            if (! $existing) {
                continue;
            }

            $entryAt = Carbon::parse($existing->entry_at);

            $ledgerRows[] = [
                'id' => $nextId,
                'type' => TransactionKind::Earning->value,
                'description' => $existing->description,
                'reference' => $existing->reference,
                'status' => TransactionStatus::Completed->value,
                'metadata' => json_encode(['source' => 'monthly-rate']),
                'entry_at' => $entryAt,
                'created_at' => $entryAt,
                'updated_at' => $entryAt,
            ];
            $entryRows[] = $this->entry($nextId, $placement['wallet_id'], TransactionEntryType::Credit,
                TransactionKind::Earning, (int) $existing->amount, $existing->description,
                $existing->reference, $entryAt);

            DB::table('wallets')->where('id', $placement['wallet_id'])->update([
                'balance' => DB::raw('balance + '.(int) $existing->amount),
                'total_earnings' => DB::raw('total_earnings + '.(int) $existing->amount),
                'available_earnings' => DB::raw('available_earnings + '.(int) $existing->amount),
            ]);

            $nextId++;
        }

        if ($ledgerRows === []) {
            return;
        }

        DB::table('ledgers')->insert($ledgerRows);
        DB::table('ledger_entries')->insert($entryRows);
        $this->resetLedgerSequence();
    }

    /**
     * An investor who concentrated several placements in the same calendar year.
     *
     * @param  list<string>  $userIds
     * @param  list<string>  $offerIds
     */
    private function seedHighVolumeInvestor(array $userIds, array $offerIds): void
    {
        $now = now();
        $userId = $userIds[count($userIds) - 1];
        $rows = [];

        foreach ([9_000, 8_000, 7_500] as $index => $amount) {
            $paidAt = $now->copy()->startOfYear()->addMonths($index * 2 + 1);
            $rows[] = [
                'id' => (string) Str::uuid7(),
                'offer_id' => $offerIds[$index],
                'user_id' => $userId,
                'status' => PlacementStatus::Active->value,
                'process' => PlacementProcess::Approved->value,
                'slug' => sprintf('AP9%05d', $index + 1),
                'automatic_withdrawal' => false,
                'grand_total_amount' => $amount * self::SCALE,
                'shares_purchased' => $amount / 100,
                'placement_payment_at' => $paidAt,
                'placement_starting_at' => $paidAt->copy()->addDays(5),
                'placement_finished_at' => $paidAt->copy()->addMonths(24),
                'total_withdrawn_enabled' => true,
                'created_at' => $paidAt,
                'updated_at' => $now,
            ];
        }

        DB::table('user_placements')->insert($rows);
    }
}
