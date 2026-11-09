<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoicePayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        if ($invoice instanceof Invoice) {
            return $this->user()?->can('update', $invoice) ?? false;
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('account_id')) {
            $this->merge(['account_id' => (int) $this->input('account_id')]);
        }
    }

    /**
     * @return array<string, mixed>
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
            'paid_date' => ['nullable', 'date'],
        ];
    }
}
