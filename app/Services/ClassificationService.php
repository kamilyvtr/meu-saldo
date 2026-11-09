<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Support\MerchantNormalizer;

final class ClassificationService
{
    /**
     * Suggest a category for the given description.
     */
    public function suggest(User $user, string $description, ?string $type = null): ?Category
    {
        $description = trim($description);

        if ($description === '') {
            return null;
        }

        // 1. Explicit rules (highest priority first).
        $rules = $user->classificationRules()
            ->with('category')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->field !== 'description' || $rule->category === null) {
                continue;
            }

            if ($type !== null && $rule->category->type->value !== $type) {
                continue;
            }

            if ($this->matches($rule->operator, $rule->pattern, $description)) {
                return $rule->category;
            }
        }

        // 2. Frequency-based suggestion.
        return $this->suggestByFrequency($user, $description, $type);
    }

    private function matches(string $operator, string $pattern, string $description): bool
    {
        $haystack = mb_strtolower($description);
        $needle = mb_strtolower($pattern);

        return match ($operator) {
            'contains' => $needle !== '' && str_contains($haystack, $needle),
            'equals' => $haystack === $needle,
            'regex' => $this->regexMatches($pattern, $description),
            default => false,
        };
    }

    private function regexMatches(string $pattern, string $description): bool
    {
        $delimiter = '/';

        if (str_starts_with($pattern, $delimiter) && substr_count($pattern, $delimiter) >= 2) {
            $regex = $pattern;
        } else {
            $regex = $delimiter.str_replace($delimiter, '\\'.$delimiter, $pattern).$delimiter.'u';
        }

        $result = @preg_match($regex, $description);

        return $result === 1;
    }

    private function suggestByFrequency(User $user, string $description, ?string $type = null): ?Category
    {
        $normalized = MerchantNormalizer::normalize($description);

        if ($normalized === '') {
            return null;
        }

        $counts = [];

        foreach ($user->transactions()->whereNotNull('category_id')->with('category')->get() as $transaction) {
            if (MerchantNormalizer::normalize($transaction->description) !== $normalized) {
                continue;
            }

            if ($transaction->category === null) {
                continue;
            }

            if ($type !== null && $transaction->category->type->value !== $type) {
                continue;
            }

            $categoryId = $transaction->category_id;
            $counts[$categoryId] = ($counts[$categoryId] ?? 0) + 1;
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);
        $topCategoryId = array_key_first($counts);

        if ($counts[$topCategoryId] < 3) {
            return null;
        }

        return Category::query()
            ->where('user_id', $user->id)
            ->whereKey($topCategoryId)
            ->first();
    }
}
