<?php

namespace App\Models;

use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int $target_cents
 * @property int $current_cents
 * @property Carbon|null $deadline
 * @property int|null $account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Account|null $account
 */
#[Fillable(['user_id', 'name', 'target_cents', 'current_cents', 'deadline', 'account_id'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_cents' => 'integer',
            'current_cents' => 'integer',
            'deadline' => 'date',
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

    public function progressPercentage(): int
    {
        if ($this->target_cents <= 0) {
            return $this->current_cents > 0 ? 100 : 0;
        }

        return (int) min(100, round(($this->current_cents / $this->target_cents) * 100));
    }

    public function getProgressAttribute(): int
    {
        return $this->progressPercentage();
    }

    public function getPercentageAttribute(): int
    {
        return $this->progressPercentage();
    }

    public function getProgressPercentageAttribute(): int
    {
        return $this->progressPercentage();
    }

    public function isCompleted(): bool
    {
        return $this->target_cents > 0 && $this->current_cents >= $this->target_cents;
    }

    public function getCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    public function remainingCents(): int
    {
        return max(0, $this->target_cents - $this->current_cents);
    }

    public function getRemainingAttribute(): int
    {
        return $this->remainingCents();
    }

    public function getRemainingCentsAttribute(): int
    {
        return $this->remainingCents();
    }

    public function deposit(int $amount): void
    {
        $this->current_cents = min($this->target_cents > 0 ? $this->target_cents : PHP_INT_MAX, $this->current_cents + $amount);
        $this->save();
    }

    public function withdraw(int $amount): void
    {
        $this->current_cents = max(0, $this->current_cents - $amount);
        $this->save();
    }
}
