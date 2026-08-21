<?php

namespace Platform\Placements\Enums;

use App\Enums\Concerns\StringifyEnum;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Actions\StateMachine\ActiveStep;
use Platform\Placements\Actions\StateMachine\CancelledStep;
use Platform\Placements\Actions\StateMachine\ContractStep;
use Platform\Placements\Actions\StateMachine\DraftStep;
use Platform\Placements\Actions\StateMachine\FinishedStep;
use Platform\Placements\Actions\StateMachine\PaymentStep;
use Platform\Placements\Actions\StateMachine\WithdrawalCompletedStep;
use Platform\Placements\Actions\StateMachine\WithdrawingStep;
use Platform\Placements\Models\Placement;

enum PlacementStatus: string
{
    use StringifyEnum;

    /**
     * Draft Status means that the placement is in the initial phase of the process.
     * The investor must define how much to reserve, against what is still available.
     */
    case Draft = 'draft';

    /**
     * Compliance Status means that the placement is under compliance analysis.
     * In this step, the user must submit the necessary documents for verification.
     * Once the documents are submitted, the placement moves to the Contract status.
     */
    case Compliance = 'compliance';

    /**
     * Contract Status means that the placement is in the contract phase.
     * In this step, the user must review and sign the investment contract.
     * Once the contract is signed, the placement moves to the Payment status.
     */
    case Contract = 'contract';

    /**
     * Payment Status means that the placement is in the payment phase.
     * Once the payment is confirmed, the placement moves to the Active status.
     */
    case Payment = 'payment';

    /**
     * Active Status means the placement is active and starts to generate returns
     * based on the investment terms.
     */
    case Active = 'active';

    /**
     * Finished Status means that the placement has reached its maturity or end date.
     * The placement is closed, and no further actions are required from the user.
     */
    case Finished = 'finished';

    /**
     * Withdrawing Status means the investor has exercised the regulated withdrawal right.
     * The placement is pending cancellation while the refund is being processed.
     * During this period the wallet is blocked and the original contract remains signed.
     */
    case Withdrawing = 'withdrawing';

    /**
     * WithdrawalCompleted Status means the regulated withdrawal has been finalized.
     * It is reached manually through the state machine once the termination agreement
     * has been signed.
     */
    case WithdrawalCompleted = 'withdrawal_completed';

    /**
     * Cancelled Status means that the placement has been terminated before completion.
     * Any reserved securities are released back to the offering.
     */
    case Cancelled = 'cancelled';

    /**
     * @throws \Exception
     */
    public function getAction(Placement $placement): AbstractPlacementStep
    {
        return match ($this) {
            self::Draft => new DraftStep($placement),
            self::Contract => new ContractStep($placement),
            self::Payment => new PaymentStep($placement),
            self::Active => new ActiveStep($placement),
            self::Finished => new FinishedStep($placement),
            self::Cancelled => new CancelledStep($placement),
            self::Withdrawing => new WithdrawingStep($placement),
            self::WithdrawalCompleted => new WithdrawalCompletedStep($placement),
            self::Compliance => throw new \Exception('To be implemented'),
        };
    }
}
