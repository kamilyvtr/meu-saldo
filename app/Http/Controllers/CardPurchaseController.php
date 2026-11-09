<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardPurchaseRequest;
use App\Models\CardPurchase;
use App\Models\CreditCard;
use App\Services\CreditCardService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CardPurchaseController extends Controller
{
    public function __construct(private readonly CreditCardService $service) {}

    public function store(CardPurchaseRequest $request): RedirectResponse
    {
        $this->authorize('create', CardPurchase::class);

        $data = $request->validated();
        $card = CreditCard::where('user_id', $request->user()->id)
            ->whereKey($data['credit_card_id'])
            ->firstOrFail();

        $this->service->purchase($request->user(), $card, [
            'description' => $data['description'],
            'total_amount' => $data['total_amount_cents'],
            'purchase_date' => $data['purchase_date'],
            'installments_count' => $data['installments_count'],
            'category_id' => $data['category_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Compra registrada com sucesso.',
        ]);

        return back();
    }

    public function destroy(CardPurchase $cardPurchase): RedirectResponse
    {
        $this->authorize('delete', $cardPurchase);

        $cardPurchase->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Compra removida.',
        ]);

        return back();
    }
}
