<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Actions\Cvm\CalculateInvestorCvmCapAction;
use Platform\Placements\Enums\PlacementCompletedKind;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Exceptions\InvestmentCapExceededException;
use Platform\Placements\Mail\PlacementActiveMail;

class PaymentStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        // Já estamos dentro de DB::transaction (aberta por parent::handle()).
        // O lockForUpdate aqui bloqueia placements concorrentes do mesmo user
        // até o commit desta transação.
        resolve(CalculateInvestorCvmCapAction::class)->assertCanInvestLocked(
            user: $this->placement->user,
            proposedAmount: $this->placement->grand_total_amount,
            excludePlacementId: $this->placement->id,
        );

        $paymentDate = now();
        $startingDate = Date::parse($paymentDate)->addDays(5);
        $finishedDate = $startingDate->copy()->addMonthsNoOverflow($this->placement->offer->duration_in_months);

        $this->placement->update([
            'status' => PlacementStatus::Active,
            'process' => PlacementProcess::Approved,
            'placement_payment_at' => $paymentDate,
            'placement_starting_at' => $startingDate,
            'placement_finished_at' => $finishedDate,
        ]);

        $this->activateWallet();
    }

    /**
     * Override to surface InvestmentCapExceededException as a meaningful
     * placement cancellation (audit trail + user notification), instead of
     * being swallowed by the parent's generic catch.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                $this->processStep();
                $this->notify();
            });
        } catch (InvestmentCapExceededException $investmentCapExceededException) {
            $this->placement->update([
                'status' => PlacementStatus::Cancelled,
                'process' => PlacementProcess::Cancelled,
                'completion_reason' => PlacementCompletedKind::CvmCapExceeded,
                'completion_notes' => sprintf(
                    'Aporte cancelado automaticamente: limite anual excedido. cap=R$ %s, ja_aportado=R$ %s, tentativa=R$ %s, restante=R$ %s.',
                    number_format($investmentCapExceededException->cap->toFloat(), 2, ',', '.'),
                    number_format($investmentCapExceededException->currentTotal->toFloat(), 2, ',', '.'),
                    number_format($investmentCapExceededException->attempted->toFloat(), 2, ',', '.'),
                    number_format($investmentCapExceededException->remaining->toFloat(), 2, ',', '.'),
                ),
            ]);
            $this->placement->wallet?->delete();
        }
    }

    public function choices(): array
    {
        return [
            PlacementStatus::Active->value => PlacementStatus::Active->value,
            PlacementStatus::Cancelled->value => PlacementStatus::Cancelled->value,
        ];
    }

    public function notify(): void
    {
        Mail::to($this->placement->user->email)->send(new PlacementActiveMail($this->placement));
    }

    public function activateWallet(): void
    {
        $this->placement->wallet()->update([
            'status' => WalletStatus::Active,
            'currency' => 'BRL',
        ]);
    }

    public function canChange(): bool
    {
        return true;
    }
}
