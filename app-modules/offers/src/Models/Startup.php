<?php

namespace Platform\Offers\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Offers\Database\Factories\StartupFactory;

class Startup extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'startup';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tax_id',
        'short_description',
        'segment',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    protected static function newFactory(): StartupFactory
    {
        return StartupFactory::new();
    }
}
