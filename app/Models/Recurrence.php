<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use Database\Factories\RecurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $description
 * @property int $amount_cents
 * @property TransactionType $type
 * @property int|null $category_id
 * @property int|null $account_id
 * @property RecurrenceFrequency $frequency
 * @property Carbon $next_run_date
 * @property Carbon|null $end_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Category|null $category
 * @property-read Account|null $account
 */
#[Fillable(['user_id', 'description', 'amount_cents', 'type', 'category_id', 'account_id', 'frequency', 'next_run_date', 'end_date', 'is_active'])]
class Recurrence extends Model
{
    /** @use HasFactory<RecurrenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'type' => TransactionType::class,
            'frequency' => RecurrenceFrequency::class,
            'next_run_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function advanceNextRunDate(): void
    {
        $date = $this->next_run_date->copy();

        $this->next_run_date = match ($this->frequency) {
            RecurrenceFrequency::Weekly => $date->addWeek(),
            RecurrenceFrequency::Monthly => $date->addMonthNoOverflow(),
            RecurrenceFrequency::Yearly => $date->addYear(),
        };
    }
}
