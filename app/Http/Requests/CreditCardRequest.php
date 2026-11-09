<?php

namespace App\Http\Requests;

use App\Models\CreditCard;
use Illuminate\Foundation\Http\FormRequest;

class CreditCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('credit_card');

        if ($card instanceof CreditCard) {
            return $this->user()?->can('update', $card) ?? false;
        }

        return $this->user()?->can('create', CreditCard::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['limit_cents', 'closing_day', 'due_day'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => (int) $this->input($field)]);
            }
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:40'],
            'institution' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'limit_cents' => ['required', 'integer', 'between:0,99999999999'],
            'closing_day' => ['required', 'integer', 'between:1,31'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
