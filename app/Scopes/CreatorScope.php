<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Creator Scope
 *
 * Global scope that automatically filters data based on the current user's identity.
 * Normal users can only see data they created, while admins can see all data.
 */
class CreatorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get the current authenticated user
        $user = request()->user() ?? auth()->user();

        // Unauthenticated users: no filtering (for public APIs or system jobs)
        if (!$user) {
            return;
        }

        // Admin users: no filtering
        if ($this->isAdmin($user)) {
            return;
        }

        // Normal users: only see data they created
        $builder->where($model->getTable() . '.create_by', $user->user_id);
    }

    /**
     * Determine if the user is an admin.
     *
     * @param mixed $user
     * @return bool
     */
    protected function isAdmin($user): bool
    {
        // user_id = 1 is super admin
        if ($user->user_id == 1) {
            return true;
        }

        // Check if user has admin role
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        return false;
    }
}
