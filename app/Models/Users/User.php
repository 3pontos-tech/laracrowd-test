<?php

namespace App\Models\Users;

use App\Models\Finance\FinanceProfile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Platform\Banking\Models\Wallet;
use Platform\Placements\Models\Placement;

class User extends Authenticatable
{
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function financeProfile(): HasOne
    {
        return $this->hasOne(FinanceProfile::class);
    }

    /**
     * @return Collection<int, Wallet>
     */
    public function wallets(): Collection
    {
        return $this->placements()
            ->has('wallet')
            ->with('wallet')
            ->get()
            ->pluck('wallet');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
