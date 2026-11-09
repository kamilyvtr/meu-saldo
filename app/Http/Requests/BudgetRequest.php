<?php

namespace App\Http\Requests;

use App\Models\Budget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('budget');

        if ($budget instanceof Budget) {
            return $this->user()?->can('update', $budget) ?? false;
        }

        return $this->user()?->can('create', Budget::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('limit_cents')) {
            $this->merge(['limit_cents' => (int) $this->input('limit_cents')]);
        } elseif ($this->has('limit')) {
            $this->merge(['limit_cents' => (int) $this->input('limit')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'limit_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
