<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Scopes\CreatorScope;
use App\Models\System\SystemUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Belongs To Creator Trait
 *
 * Provides data isolation capability for models.
 * Automatically registers CreatorScope and provides ownership check methods.
 */
trait BelongsToCreator
{
    /**
     * Boot the trait.
     *
     * Registers the CreatorScope global scope.
     *
     * @return void
     */
    public static function bootBelongsToCreator(): void
    {
        static::addGlobalScope(new CreatorScope());
    }

    /**
     * Check if the current user is the creator of this record.
     *
     * @return bool
     */
    public function isOwnedByCurrentUser(): bool
    {
        $user = request()->user() ?? auth()->user();

        if (!$user) {
            return false;
        }

        return $this->create_by == $user->user_id;
    }

    /**
     * Check if the current user has permission to modify this record.
     * Admins or creators have permission.
     *
     * @return bool
     */
    public function canBeModifiedByCurrentUser(): bool
    {
        $user = request()->user() ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Admins have permission
        if ($this->isCurrentUserAdmin($user)) {
            return true;
        }

        // Creators have permission
        return $this->isOwnedByCurrentUser();
    }

    /**
     * Determine if the current user is an admin.
     *
     * @param mixed $user
     * @return bool
     */
    protected function isCurrentUserAdmin($user): bool
    {
        if ($user->user_id == 1) {
            return true;
        }

        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        return false;
    }

    /**
     * Local scope: only query data created by the current user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedByMe($query)
    {
        $user = request()->user() ?? auth()->user();

        if ($user) {
            return $query->where('create_by', $user->user_id);
        }

        return $query;
    }

    /**
     * Get the creator relationship.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(SystemUser::class, 'create_by', 'user_id');
    }
}
