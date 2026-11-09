<?php

namespace App\Models;

use Database\Factories\CreditCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $account_id
 * @property string $name
 * @property string|null $brand
 * @property int $limit_cents
 * @property int $closing_day
 * @property int $due_day
 * @property string|null $color
 * @property string|null $institution
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Account|null $account
 * @property-read Collection<int, CardPurchase> $purchases
 * @property-read Collection<int, Installment> $installments
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int $debt_cents
 * @property-read int $available_limit_cents
 */
#[Fillable(['user_id', 'account_id', 'name', 'brand', 'limit_cents', 'closing_day', 'due_day', 'color', 'institution', 'is_active'])]
class CreditCard extends Model
{
    /** @use HasFactory<CreditCardFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'limit_cents' => 'integer',
            'closing_day' => 'integer',
            'due_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany<CardPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(CardPurchase::class);
    }

    /**
     * @return HasMany<Installment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getDebtCentsAttribute(): int
    {
        if ($this->account instanceof Account) {
            return max(0, -(int) $this->account->balance);
        }

        return (int) $this->installments()
            ->where('status', 'pending')
            ->sum('amount');
    }

    public function getAvailableLimitCentsAttribute(): int
    {
        return max(0, (int) $this->limit_cents - $this->debt_cents);
    }

    public function getDebtAttribute(): int
    {
        return $this->debt_cents;
    }

    public function getUsedCentsAttribute(): int
    {
        return $this->debt_cents;
    }
}
