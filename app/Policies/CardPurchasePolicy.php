<?php

namespace App\Policies;

use App\Models\CardPurchase;
use App\Models\User;

class CardPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CardPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CardPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id;
    }

    public function delete(User $user, CardPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id;
    }

    public function restore(User $user, CardPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id;
    }

    public function forceDelete(User $user, CardPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id;
    }
}
