<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        if ($transaction instanceof Transaction) {
            return $this->user()?->can('update', $transaction) ?? false;
        }

        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount_cents')) {
            $this->merge([
                'amount_cents' => (int) $this->input('amount_cents'),
            ]);
        }

        if ($this->input('category_id') === '' || $this->input('category_id') === 'null') {
            $this->merge(['category_id' => null]);
        }

        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount_cents' => ['required', 'integer', 'between:1,99999999999'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
