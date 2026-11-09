<?php

namespace App\Http\Requests;

use App\Models\Recurrence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recurrence = $this->route('recurrence');

        if ($recurrence instanceof Recurrence) {
            return $this->user()?->can('update', $recurrence) ?? false;
        }

        return $this->user()?->can('create', Recurrence::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount_cents')) {
            $this->merge(['amount_cents' => (int) $this->input('amount_cents')]);
        } elseif ($this->has('amount')) {
            $this->merge(['amount_cents' => (int) $this->input('amount')]);
        }

        if ($this->has('account_id') && $this->input('account_id') === '') {
            $this->merge(['account_id' => null]);
        }

        if ($this->has('category_id') && $this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $this->input('is_active')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'description' => ['required', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'between:1,99999999999'],
            'type' => ['required', 'string', 'in:income,expense'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)],
            'frequency' => ['required', 'string', 'in:weekly,monthly,yearly'],
            'next_run_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:next_run_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
