<?php

declare(strict_types=1);

namespace App\Models\Traits;

/**
 * Has Audit Fields Trait
 *
 * Provides automatic population of audit fields (create_by, update_by)
 * when creating or updating model records.
 */
trait HasAuditFields
{
    /**
     * Boot the trait.
     *
     * Registers model event listeners to automatically populate
     * audit fields with the authenticated user's userid.
     *
     * @return void
     */
    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            $userId = static::getAuthenticatedUserId();
            if (!$model->isDirty('create_by')) {
                $model->create_by = $userId;
            }
            if (!$model->isDirty('update_by')) {
                $model->update_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = static::getAuthenticatedUserId();
            if (!$model->isDirty('update_by')) {
                $model->update_by = $userId;
            }
        });
    }

    /**
     * Get the authenticated user's userid.
     *
     * Supports both Laravel's default auth guard and JWT authentication
     * via request user resolver.
     *
     * @return int|null
     */
    protected static function getAuthenticatedUserId(): ?int
    {
        // Try request user resolver first (for JWT authentication)
        $user = request()->user();
        // Fallback to auth guard
        if (!$user) {
            $user = auth()->user();
        }

        return $user->user_id ?? null;
    }

    /**
     * Initialize the trait.
     *
     * Merges audit fields into the model's fillable attributes.
     *
     * @return void
     */
    public function initializeHasAuditFields(): void
    {
        $this->fillable = array_merge($this->fillable, ['create_by', 'update_by']);
    }
}
