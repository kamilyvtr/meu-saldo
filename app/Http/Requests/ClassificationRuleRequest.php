<?php

namespace App\Http\Requests;

use App\Models\ClassificationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('classification_rule');

        if ($rule instanceof ClassificationRule) {
            return $this->user()?->can('update', $rule) ?? false;
        }

        return $this->user()?->can('create', ClassificationRule::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'field' => ['required', 'string', 'in:description'],
            'operator' => ['required', 'string', 'in:contains,equals,regex'],
            'pattern' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('priority') === '' || $this->input('priority') === null) {
            $this->merge(['priority' => 0]);
        }
    }
}
