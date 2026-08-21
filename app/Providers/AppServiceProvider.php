<?php

namespace App\Providers;

use App\Models\Finance\FinanceProfile;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Transactions\Transaction;
use Platform\Banking\Models\Wallet;
use Platform\Offers\Models\Offer;
use Platform\Offers\Models\Rate;
use Platform\Offers\Models\Startup;
use Platform\Placements\Models\Contract;
use Platform\Placements\Models\Placement;
use Platform\Placements\Models\Template;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'users' => User::class,
            'finance_profiles' => FinanceProfile::class,
            'wallets' => Wallet::class,
            'transactions' => Transaction::class,
            'ledgers' => Ledger::class,
            'offers' => Offer::class,
            'rates' => Rate::class,
            'startups' => Startup::class,
            'placements' => Placement::class,
            'contracts' => Contract::class,
            'templates' => Template::class,
        ]);
    }
}
