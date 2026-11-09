<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Recurrence;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurrencesCommand extends Command
{
    protected $signature = 'recurrences:process';

    protected $description = 'Processa recorrências vencidas e gera as transações correspondentes';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $recurrences = Recurrence::query()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        $processed = 0;

        foreach ($recurrences as $recurrence) {
            DB::transaction(function () use ($recurrence, &$processed): void {
                // Avoid duplicates: only process while the due date is today or past and within end_date.
                while (true) {
                    $runDate = $recurrence->next_run_date->copy();

                    if ($runDate->toDateString() > Carbon::today()->toDateString()) {
                        break;
                    }

                    if ($recurrence->end_date !== null && $runDate->gt($recurrence->end_date)) {
                        $recurrence->is_active = false;
                        $recurrence->save();
                        break;
                    }

                    // Idempotency guard: skip if an identical transaction already exists for this date.
                    $alreadyExists = Transaction::query()
                        ->where('user_id', $recurrence->user_id)
                        ->where('account_id', $recurrence->account_id)
                        ->where('description', $recurrence->description)
                        ->where('type', $recurrence->type->value)
                        ->where('amount', $recurrence->amount_cents)
                        ->whereDate('date', $runDate->toDateString())
                        ->exists();

                    $account = $recurrence->user->accounts()->whereKey($recurrence->account_id)->first();

                    if (! $account instanceof Account) {
                        $recurrence->is_active = false;
                        $recurrence->save();
                        break;
                    }

                    if (! $alreadyExists) {
                        $recurrence->user->transactions()->create([
                            'account_id' => $recurrence->account_id,
                            'category_id' => $recurrence->category_id,
                            'description' => $recurrence->description,
                            'type' => $recurrence->type->value,
                            'amount' => $recurrence->amount_cents,
                            'date' => $runDate->toDateString(),
                        ]);

                        $delta = $recurrence->type === TransactionType::Income
                            ? $recurrence->amount_cents
                            : -$recurrence->amount_cents;
                        $account->increment('balance', $delta);
                    }

                    $recurrence->advanceNextRunDate();
                    if ($recurrence->end_date !== null && $recurrence->next_run_date->gt($recurrence->end_date)) {
                        $recurrence->is_active = false;
                    }
                    $recurrence->save();

                    $processed++;
                }
            });
        }

        $this->info("Recorrências processadas: {$processed}");

        return self::SUCCESS;
    }
}
