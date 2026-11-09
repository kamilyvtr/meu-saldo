<?php

namespace App\Http\Requests;

use App\Models\Goal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $goal = $this->route('goal');

        if ($goal instanceof Goal) {
            return $this->user()?->can('update', $goal) ?? false;
        }

        return $this->user()?->can('create', Goal::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('target_cents')) {
            $this->merge(['target_cents' => (int) $this->input('target_cents')]);
        } elseif ($this->has('target')) {
            $this->merge(['target_cents' => (int) $this->input('target')]);
        }

        if ($this->has('current_cents')) {
            $this->merge(['current_cents' => (int) $this->input('current_cents')]);
        } elseif ($this->has('current')) {
            $this->merge(['current_cents' => (int) $this->input('current')]);
        }

        if ($this->has('account_id') && $this->input('account_id') === '') {
            $this->merge(['account_id' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'target_cents' => ['required', 'integer', 'min:1'],
            'current_cents' => ['sometimes', 'integer', 'min:0'],
            'deadline' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];
    }
}
