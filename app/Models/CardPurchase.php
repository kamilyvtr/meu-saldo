<?php

namespace App\Models;

use Database\Factories\CardPurchaseFactory;
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
 * @property int $credit_card_id
 * @property int|null $category_id
 * @property string $description
 * @property int $total_amount
 * @property Carbon $purchase_date
 * @property int $installments_count
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read CreditCard $creditCard
 * @property-read Category|null $category
 * @property-read Collection<int, Installment> $installments
 */
#[Fillable(['user_id', 'credit_card_id', 'category_id', 'description', 'total_amount', 'purchase_date', 'installments_count', 'notes'])]
class CardPurchase extends Model
{
    /** @use HasFactory<CardPurchaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'installments_count' => 'integer',
            'purchase_date' => 'date',
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
     * @return BelongsTo<CreditCard, $this>
     */
    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Installment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
