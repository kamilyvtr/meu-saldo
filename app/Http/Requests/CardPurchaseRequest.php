<?php

namespace App\Http\Requests;

use App\Models\CardPurchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CardPurchase::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('total_amount_cents')) {
            $this->merge(['total_amount_cents' => (int) $this->input('total_amount_cents')]);
        }

        if ($this->has('installments_count')) {
            $this->merge(['installments_count' => (int) $this->input('installments_count')]);
        }

        if ($this->input('category_id') === '' || $this->input('category_id') === 'null') {
            $this->merge(['category_id' => null]);
        }

        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'credit_card_id' => [
                'required',
                'integer',
                Rule::exists('credit_cards', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'description' => ['required', 'string', 'max:255'],
            'total_amount_cents' => ['required', 'integer', 'between:1,99999999999'],
            'purchase_date' => ['required', 'date'],
            'installments_count' => ['required', 'integer', 'between:1,60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
