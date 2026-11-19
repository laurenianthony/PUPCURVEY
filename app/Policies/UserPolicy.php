<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        if ($model->role === 'super_admin') {
            return $user->id === $model->id;
        }

        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function update(User $user, User $model): bool
    {
        if ($model->role === 'super_admin') {
            return $user->id === $model->id;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'admin' && $model->role === 'user';
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->role === 'super_admin') {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'admin' && $model->role === 'user';
    }

    public function promoteOrDemote(User $user, User $model): bool
    {
        return $user->role === 'super_admin'
            && $model->role !== 'super_admin'
            && $model->status !== 'pending';
    }
}
