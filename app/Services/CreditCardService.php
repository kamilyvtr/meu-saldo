<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\InstallmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\CardPurchase;
use App\Models\CreditCard;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreditCardService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCard(User $user, array $data): CreditCard
    {
        return DB::transaction(function () use ($user, $data): CreditCard {
            $accountId = $data['account_id'] ?? null;

            if (! $accountId) {
                $account = Account::create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'type' => AccountType::CreditCard,
                    'balance' => 0,
                    'color' => $data['color'] ?? null,
                    'institution' => $data['institution'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
                $accountId = $account->id;
            }

            return CreditCard::create([
                'user_id' => $user->id,
                'account_id' => $accountId,
                'name' => $data['name'],
                'brand' => $data['brand'] ?? null,
                'limit_cents' => (int) ($data['limit_cents'] ?? 0),
                'closing_day' => (int) ($data['closing_day'] ?? 1),
                'due_day' => (int) ($data['due_day'] ?? 10),
                'color' => $data['color'] ?? null,
                'institution' => $data['institution'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function purchase(User $user, CreditCard $card, array $data): CardPurchase
    {
        if ($card->user_id !== $user->id) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $card, $data): CardPurchase {
            $total = (int) $data['total_amount'];
            $count = max(1, (int) ($data['installments_count'] ?? 1));
            $purchaseDate = Carbon::parse($data['purchase_date']);

            $purchase = CardPurchase::create([
                'user_id' => $user->id,
                'credit_card_id' => $card->id,
                'description' => $data['description'],
                'total_amount' => $total,
                'purchase_date' => $purchaseDate->toDateString(),
                'installments_count' => $count,
                'category_id' => $data['category_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Despesa operacional no mês da compra (não duplicada no pagamento).
            $account = $card->account()->first();
            if (! $account instanceof Account) {
                $account = $user->accounts()->create([
                    'name' => $card->name,
                    'type' => AccountType::CreditCard,
                    'balance' => 0,
                    'color' => $card->color,
                    'institution' => $card->institution,
                    'is_active' => $card->is_active,
                ]);
                $card->update(['account_id' => $account->id]);
            }

            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'],
                'type' => TransactionType::Expense,
                'amount' => $total,
                'date' => $purchaseDate->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            $account->increment('balance', -$total);

            // Split total em N parcelas.
            $base = intdiv($total, $count);
            $remainder = $total % $count;
            $amounts = [];
            for ($i = 0; $i < $count; $i++) {
                $amounts[] = $base + ($i < $remainder ? 1 : 0);
            }

            $installments = [];
            for ($i = 0; $i < $count; $i++) {
                $dueDate = $purchaseDate->copy()->addMonthsNoOverflow($i);
                $installments[] = [
                    'number' => $i + 1,
                    'amount' => $amounts[$i],
                    'due_date' => $dueDate->toDateString(),
                ];
            }

            // Agrupa parcelas por mês em faturas.
            $grouped = collect($installments)->groupBy(function (array $item): string {
                return Carbon::parse($item['due_date'])->format('Y-m');
            });

            foreach ($grouped as $month => $items) {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $closingDay = min(max(1, $card->closing_day), $start->daysInMonth);
                $dueDay = min(max(1, $card->due_day), $start->daysInMonth);
                $closingDate = $start->copy()->day($closingDay);
                $dueDate = $start->copy()->day($dueDay);

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'credit_card_id' => $card->id,
                    'month' => $month,
                    'closing_date' => $closingDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'status' => InvoiceStatus::Open,
                    'total_amount' => (int) collect($items)->sum('amount'),
                ]);

                foreach ($items as $item) {
                    Installment::create([
                        'user_id' => $user->id,
                        'credit_card_id' => $card->id,
                        'card_purchase_id' => $purchase->id,
                        'invoice_id' => $invoice->id,
                        'number' => $item['number'],
                        'amount' => $item['amount'],
                        'due_date' => $item['due_date'],
                        'status' => InstallmentStatus::Pending,
                    ]);
                }
            }

            return $purchase;
        });
    }

    public function payInvoice(User $user, Invoice $invoice, int $bankAccountId, ?string $date = null): Invoice
    {
        if ($invoice->user_id !== $user->id) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $invoice, $bankAccountId, $date): Invoice {
            if ($invoice->status === InvoiceStatus::Paid) {
                return $invoice;
            }

            $bank = $user->accounts()->whereKey($bankAccountId)->firstOrFail();
            $card = $invoice->creditCard;
            $total = (int) $invoice->total_amount;

            // Debita saldo da conta bancária.
            $bank->increment('balance', -$total);

            // Reduz dívida do cartão, sem criar nova despesa.
            $cardAccount = $card->account()->first();
            if ($cardAccount instanceof Account) {
                $cardAccount->increment('balance', $total);
            }

            $paidAt = $date ? Carbon::parse($date) : now();

            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $paidAt,
                'paid_from_account_id' => $bank->id,
            ]);

            $invoice->installments()->update([
                'status' => InstallmentStatus::Paid,
                'paid_at' => $paidAt,
            ]);

            return $invoice->fresh();
        });
    }

    public function closeInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Open) {
            $invoice->update(['status' => InvoiceStatus::Closed]);
        }

        return $invoice->fresh();
    }
}
