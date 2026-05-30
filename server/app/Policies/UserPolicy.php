<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function view(User $authUser, User $user): bool
    {
        return true;
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->is($user) || $authUser->isAdmin();
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->is($user) || $authUser->isAdmin();
    }

    public function suspend(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && ! $authUser->is($user);
    }

    public function ban(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && ! $authUser->is($user);
    }

    public function activate(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && ! $authUser->is($user);
    }
}
