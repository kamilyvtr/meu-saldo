<?php

namespace App\Policies;

use App\Models\ClassificationRule;
use App\Models\User;

class ClassificationRulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClassificationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ClassificationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }

    public function delete(User $user, ClassificationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }
}
