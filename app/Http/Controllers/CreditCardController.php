<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreditCardRequest;
use App\Models\CreditCard;
use App\Services\CreditCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditCardController extends Controller
{
    public function __construct(private readonly CreditCardService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CreditCard::class);

        $user = $request->user();

        $cards = $user->creditCards()
            ->with('account')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (CreditCard $card): array => $this->cardPayload($card))
            ->values()
            ->all();

        return Inertia::render('credit-cards/Index', [
            'cards' => $cards,
        ]);
    }

    public function store(CreditCardRequest $request): RedirectResponse
    {
        $this->authorize('create', CreditCard::class);

        $data = $request->validated();
        $data['limit_cents'] = $data['limit_cents'] ?? 0;

        $this->service->createCard($request->user(), $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cartão criado com sucesso.',
        ]);

        return back();
    }

    public function show(Request $request, CreditCard $creditCard): Response
    {
        $this->authorize('view', $creditCard);

        $creditCard->load(['account', 'purchases.category', 'invoices.installments', 'installments']);

        return Inertia::render('credit-cards/Show', [
            'card' => $this->cardPayload($creditCard),
            'purchases' => $creditCard->purchases->map(fn ($p) => [
                'id' => $p->id,
                'description' => $p->description,
                'total_amount' => $p->total_amount,
                'purchase_date' => $p->purchase_date->toDateString(),
                'installments_count' => $p->installments_count,
                'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name, 'color' => $p->category->color] : null,
            ])->values()->all(),
            'invoices' => $creditCard->invoices->sortByDesc('month')->values()->map(fn ($inv) => [
                'id' => $inv->id,
                'month' => $inv->month,
                'status' => $inv->status->value,
                'status_label' => $inv->status->label(),
                'total_amount' => $inv->total_amount,
                'due_date' => $inv->due_date?->toDateString(),
                'paid_at' => $inv->paid_at?->toDateTimeString(),
            ])->values()->all(),
        ]);
    }

    public function update(CreditCardRequest $request, CreditCard $creditCard): RedirectResponse
    {
        $this->authorize('update', $creditCard);

        $data = $request->validated();

        $creditCard->update([
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'limit_cents' => (int) ($data['limit_cents'] ?? 0),
            'closing_day' => (int) $data['closing_day'],
            'due_day' => (int) $data['due_day'],
            'color' => $data['color'] ?? null,
            'institution' => $data['institution'] ?? null,
            'is_active' => $data['is_active'] ?? $creditCard->is_active,
        ]);

        if ($creditCard->account) {
            $creditCard->account->update([
                'name' => $data['name'],
                'color' => $data['color'] ?? null,
                'institution' => $data['institution'] ?? null,
                'is_active' => $data['is_active'] ?? $creditCard->account->is_active,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cartão atualizado com sucesso.',
        ]);

        return back();
    }

    public function destroy(CreditCard $creditCard): RedirectResponse
    {
        $this->authorize('delete', $creditCard);

        $creditCard->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cartão removido.',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(CreditCard $card): array
    {
        return [
            'id' => $card->id,
            'name' => $card->name,
            'brand' => $card->brand,
            'limit_cents' => $card->limit_cents,
            'closing_day' => $card->closing_day,
            'due_day' => $card->due_day,
            'color' => $card->color,
            'institution' => $card->institution,
            'is_active' => $card->is_active,
            'debt_cents' => $card->debt_cents,
            'available_limit_cents' => $card->available_limit_cents,
            'account_id' => $card->account_id,
        ];
    }
}
