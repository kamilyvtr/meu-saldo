<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $month
 * @property int $limit_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Category|null $category
 * @property-read int $spent
 * @property-read int $spent_cents
 */
#[Fillable(['user_id', 'category_id', 'month', 'limit_cents'])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'limit_cents' => 'integer',
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function spentCents(): int
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $this->month)->endOfMonth()->toDateString();

        return (int) Transaction::query()
            ->where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');
    }

    public function spent(): int
    {
        return $this->spentCents();
    }

    public function getSpentAttribute(): int
    {
        return $this->spentCents();
    }

    public function getSpentCentsAttribute(): int
    {
        return $this->spentCents();
    }

    public function remainingCents(): int
    {
        return $this->limit_cents - $this->spentCents();
    }

    public function getRemainingAttribute(): int
    {
        return $this->remainingCents();
    }

    public function getRemainingCentsAttribute(): int
    {
        return $this->remainingCents();
    }

    public function isOverBudget(): bool
    {
        return $this->spentCents() > $this->limit_cents;
    }

    public function getIsOverAttribute(): bool
    {
        return $this->isOverBudget();
    }

    public function getOverBudgetAttribute(): bool
    {
        return $this->isOverBudget();
    }

    public function percentage(): int
    {
        if ($this->limit_cents <= 0) {
            return $this->spentCents() > 0 ? 100 : 0;
        }

        return (int) min(100, round(($this->spentCents() / $this->limit_cents) * 100));
    }

    public function getPercentageAttribute(): int
    {
        return $this->percentage();
    }

    public function getProgressAttribute(): int
    {
        return $this->percentage();
    }
}
