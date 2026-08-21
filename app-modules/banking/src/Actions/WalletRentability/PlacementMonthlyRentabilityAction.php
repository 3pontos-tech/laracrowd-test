<?php

namespace Platform\Banking\Actions\WalletRentability;

use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Actions\CreateWallet\WalletTransactionEntryDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Enums\Wallet\MonthlyEarningsReport\ReportStatus;
use Platform\Banking\Enums\Wallet\MonthlyEarningsReport\SkipReason;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Transactions\Transaction;
use Platform\Banking\Models\Wallet;
use Platform\Banking\Withdrawals\Actions\ApproveWithdrawalAction;
use Platform\Banking\Withdrawals\Actions\RequestWithdrawalAction;
use Platform\Banking\Withdrawals\DTOs\RequestWithdrawalDTO;
use Platform\Offers\Models\Rate;
use Platform\Placements\Models\Placement;

use function Illuminate\Support\days;

readonly class PlacementMonthlyRentabilityAction
{
    public function __construct(
        private WalletRentabilityAction $rentabilityAction,
        private CreateWalletTransaction $createWalletTransaction,
        private RequestWithdrawalAction $requestWithdrawalAction,
        private ApproveWithdrawalAction $approveWithdrawalAction,
    ) {}

    public function handle(Placement $placement, CarbonInterface $targetMonth, bool $dryRun = true): MonthlyEarningsReportDTO
    {
        /** @var Wallet $wallet */
        $wallet = $placement->wallet;
        $rate = $this->findValidRateForMonth($placement);

        try {
            $this->validatePlacement($rate, $wallet, $placement, $targetMonth);

            $daysToCalculate = $this->retrieveDaysToCalculate($placement, $rate);

            // Calcular rentabilidade
            $dto = new CalculateWalletRentabilityDTO(
                balance: $wallet->balance->amount,
                rate: $rate->value->amount,
                rentabilityMonth: $targetMonth,
                rentabilityPeriodInDays: $daysToCalculate
            );

            $rentabilityResponse = $this->rentabilityAction->handle($dto);

            if ($rentabilityResponse->rentabilityAmount <= 0) {
                throw WalletRentabilityException::of(SkipReason::EarningZeroOrNegative);
            }

        } catch (WalletRentabilityException $walletRentabilityException) {
            return new MonthlyEarningsReportDTO(
                placementSlug: $placement->slug,
                userName: $placement->user->name,
                walletBalanceBefore: $wallet?->balance ?? Money::from(0),
                walletBalanceAfter: $wallet?->balance ?? Money::from(0),
                earningAmount: 0.0,
                status: ReportStatus::Skipped,
                skipReason: $walletRentabilityException->getReason()
            );
        }

        // Criar transação em modo produção
        if (! $dryRun) {
            $this->createEarningTransaction(
                $wallet,
                $placement,
                $rate,
                $rentabilityResponse->rentabilityAmount,
                $targetMonth->format('Y-m')
            );
        }

        $canAutomaticWithdraw = $placement->automatic_withdrawal
            && $placement->canWithdrawForMonth($targetMonth);

        if ($canAutomaticWithdraw) {

            // Monta o valor que será transferido para withdraw (em moeda do wallet)
            if ($placement->total_withdrawn_enabled) {
                $withdrawalAmount = $wallet->available_earnings->amount->plus($rentabilityResponse->rentabilityAmountInCurrency);
                $walletFakeResponse = $wallet->balance->amount
                    ->minus($withdrawalAmount)
                    ->plus($rentabilityResponse->rentabilityAmountInCurrency);
            } else {
                $withdrawalAmount = $rentabilityResponse->rentabilityAmountInCurrency;
                $walletFakeResponse = $wallet->balance->amount;
            }

            if (! $dryRun) {
                $wallet->refresh();

                $ledger = $this->requestWithdrawalAction->handle(new RequestWithdrawalDTO(
                    placement: $placement,
                    kind: TransactionKind::Earning,
                    amount: Money::from($withdrawalAmount),
                ));

                $this->approveWithdrawalAction->handle($ledger);
            }

            return new MonthlyEarningsReportDTO(
                placementSlug: $placement->slug,
                userName: $placement->user->name,
                walletBalanceBefore: $wallet->balance,
                walletBalanceAfter: new Money($walletFakeResponse),
                earningAmount: $rentabilityResponse->rentabilityAmount,
                status: ReportStatus::Processed,
                rentabilityDays: $daysToCalculate,
                withdrawalAmount: $withdrawalAmount->toFloat()
            );
        }

        return new MonthlyEarningsReportDTO(
            placementSlug: $placement->slug,
            userName: $placement->user->name,
            walletBalanceBefore: $wallet->balance,
            walletBalanceAfter: new Money($rentabilityResponse->balance),
            earningAmount: $rentabilityResponse->rentabilityAmount,
            status: ReportStatus::Processed,
            rentabilityDays: $daysToCalculate
        );

    }

    /**
     * @throws \Throwable
     */
    private function createEarningTransaction(
        Wallet $wallet,
        Placement $placement,
        object $rate,
        float $earningAmount,
        string $period
    ): void {
        $transactionDTO = new NewWalletTransactionDTO(
            entryType: TransactionEntryType::Credit,
            kind: TransactionKind::Earning,
            wallets: [
                new WalletTransactionEntryDTO($wallet->getKey(), TransactionEntryType::Credit),
            ],
            amount: Money::from($earningAmount),
            description: 'Monthly earnings for period '.$period,
            reference: sprintf('earning-%s-%s', $period, $placement->slug),
            entryAt: now(),
            metadata: [
                'period' => $period,
                'source' => 'monthly-rate',
                'offer_id' => $placement->offer_id,
                'placement_id' => $placement->getKey(),
                'rate_value' => $rate->value,
                'rate_id' => $rate->getKey(),
            ]
        );

        $this->createWalletTransaction->newTransaction($transactionDTO);

        $cacheKey = sprintf('has-wallet-earnings-report-%s-%s', $wallet->getKey(), $period);
        Cache::tags(['wallet'])->put($cacheKey, true, days(15));
    }

    private function validatePlacement(?Rate $rate, ?Wallet $wallet, Placement $placement, CarbonInterface $targetMonth): void
    {
        if (! $rate instanceof Rate) {
            throw WalletRentabilityException::of(SkipReason::MissingRate);
        }

        if (! $wallet instanceof Wallet) {
            throw WalletRentabilityException::of(SkipReason::BalanceZeroOrNegative);
        }

        if ($this->hasExistingEarningForPeriod($placement, $targetMonth)) {
            throw WalletRentabilityException::of(SkipReason::AlreadyProcessed);
        }

        if ($this->hasPendingWithdrawals($wallet)) {
            throw WalletRentabilityException::of(SkipReason::PendingWithdrawals);
        }
    }

    private function hasPendingWithdrawals(Wallet $wallet): bool
    {
        return Ledger::query()
            ->where('status', TransactionStatus::Pending)
            ->whereHas('transactions', fn ($q) => $q
                ->where('wallet_id', $wallet->getKey())
                ->where('entry_type', TransactionEntryType::Debit))
            ->exists();
    }

    private function hasExistingEarningForPeriod(Placement $placement, CarbonInterface $targetMonth): bool
    {
        $reference = sprintf('earning-%s-%s', $targetMonth->format('Y-m'), $placement->slug);

        return Transaction::query()->where('reference', $reference)->exists();
    }

    private function findValidRateForMonth(Placement $placement): ?Rate
    {
        return $placement->offer->currentRate;
    }

    private function retrieveDaysToCalculate(Placement $placement, Rate $rate): int
    {
        // 1. Placement payment date + 5 days (old system ALWAYS does this)
        $placementPaymentAt = Date::parse($placement->placement_payment_at)->addDays(5);

        // 2. Rentability starting date (usually == placement_payment_at, but normalized)
        $rentabilityStartingAt = Date::parse($placement->placement_starting_at);

        // 3. Start of month (Carbon-equivalent)
        $startOfMonth = Date::createFromDate($rate->starting_at->year, $rate->starting_at->month, 1);
        $daysInMonth = $startOfMonth->daysInMonth;

        // 4. End of month (Carbon does NOT use daysInMonth parameter; it uses ->endOfMonth())
        $endOfMonth = Date::createFromDate($rate->starting_at->year, $rate->starting_at->month, 1)
            ->endOfMonth();

        // 5. Exact same skip rule
        if ($rentabilityStartingAt->isAfter($endOfMonth)) {

            throw WalletRentabilityException::of(reason: SkipReason::EarningsNextMonth);
        }

        // 6. EXACT same rule as old system:
        //
        // If aporte occurs in the same Y/M as the end-of-month,
        // return diffInDays + 1, otherwise return full month.
        //
        $sameMonthAndYear =
            $placementPaymentAt->month === $endOfMonth->month &&
            $placementPaymentAt->year === $endOfMonth->year;

        $daysToCalculate = $sameMonthAndYear ? $endOfMonth->diffInDays($placementPaymentAt) : $daysInMonth;

        return abs($daysToCalculate);
    }
}
