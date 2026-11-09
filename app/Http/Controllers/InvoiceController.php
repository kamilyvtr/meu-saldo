<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\InvoicePayRequest;
use App\Models\Account;
use App\Models\Invoice;
use App\Services\CreditCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly CreditCardService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $user = $request->user();

        $invoices = $user->invoices()
            ->with(['creditCard', 'paidFromAccount'])
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'credit_card' => [
                    'id' => $invoice->creditCard->id,
                    'name' => $invoice->creditCard->name,
                ],
                'month' => $invoice->month,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total_amount' => $invoice->total_amount,
                'due_date' => $invoice->due_date?->toDateString(),
                'paid_at' => $invoice->paid_at?->toDateTimeString(),
                'paid_from' => $invoice->paidFromAccount?->name,
            ])
            ->values()
            ->all();

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->where('type', '!=', AccountType::CreditCard)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'balance'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'balance' => $account->balance,
            ])
            ->values()
            ->all();

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
            'accounts' => $accounts,
        ]);
    }

    public function pay(InvoicePayRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        $this->service->payInvoice(
            $request->user(),
            $invoice,
            (int) $data['account_id'],
            $data['paid_date'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Fatura paga com sucesso.',
        ]);

        return back();
    }

    public function close(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->service->closeInvoice($invoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Fatura fechada.',
        ]);

        return back();
    }
}
