<?php

namespace App\Policies;

use App\Models\Recurrence;
use App\Models\User;

class RecurrencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Recurrence $recurrence): bool
    {
        return $recurrence->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Recurrence $recurrence): bool
    {
        return $recurrence->user_id === $user->id;
    }

    public function delete(User $user, Recurrence $recurrence): bool
    {
        return $recurrence->user_id === $user->id;
    }

    public function restore(User $user, Recurrence $recurrence): bool
    {
        return $recurrence->user_id === $user->id;
    }

    public function forceDelete(User $user, Recurrence $recurrence): bool
    {
        return $recurrence->user_id === $user->id;
    }
}