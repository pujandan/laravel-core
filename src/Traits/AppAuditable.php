<?php

namespace Daniardev\LaravelTsd\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait AppAuditable
{
    /**
     * Boot the app auditable trait for a model.
     *
     * @return void
     */
    protected static function bootAppAuditable(): void
    {
        // Autopopulate audit fields when creating a new model
        static::creating(function (Model $model) {
            if (Auth::check()) {
                $authId = Auth::id();

                // Set both created_by and updated_by on creation
                $model->setAttribute('created_by', $authId);
                $model->setAttribute('updated_by', $authId);
            }
        });

        // Autopopulate updated_by when updating a model
        static::updating(function (Model $model) {
            if (Auth::check()) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        // Autopopulate deleted_by when soft deleting a model
        static::deleting(function (Model $model) {
            if (Auth::check() && method_exists($model, 'trashed')) {
                // Only set deleted_by if it's a soft delete
                if ($model->isSoftDeleting()) {
                    $model->setAttribute('deleted_by', Auth::id());

                    // Save the deleted_by before the model is soft deleted
                    // Use saveQuietly to avoid triggering updated_at and updated_by
                    $model->saveQuietly();
                }
            }
        });

        // Clear deleted_by when restoring a soft deleted model
        // ONLY register this event if the model uses SoftDeletes trait
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restoring(function (Model $model) {
                if (Auth::check()) {
                    $model->setAttribute('deleted_by', null);
                }
            });
        }
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * Get a fresh timestamp with microseconds for the model.
     *
     * @return Carbon
     */
    public function freshTimestamp(): Carbon
    {
        // Get current time with microseconds
        return Carbon::now();
    }

    /**
     * Get a fresh timestamp string for the model.
     *
     * @return string
     */
    public function freshTimestampString(): string
    {
        // Return timestamp with microsecond precision
        return Carbon::now()->format('Y-m-d H:i:s.u');
    }

    /**
     * Serialize date to microsecond format.
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s.u');
    }

    /**
     * Check if the model is currently soft deleting.
     *
     * @return bool
     */
    protected function isSoftDeleting(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
    }

    /**
     * Relationship: User who created this model.
     *
     * Uses auth.providers.users.model config or defaults to App\Models\User
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class),
            'created_by'
        );
    }

    /**
     * Relationship: User who last updated this model.
     *
     * Uses auth.providers.users.model config or defaults to App\Models\User
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class),
            'updated_by'
        );
    }

    /**
     * Relationship: User who deleted this model.
     *
     * Uses auth.providers.users.model config or defaults to App\Models\User
     *
     * @return BelongsTo
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class),
            'deleted_by'
        );
    }

    /**
     * Scope: Filter by creator.
     *
     * @param Builder $query
     * @param string|int $userId
     * @return Builder
     */
    public function scopeCreatedBy(Builder $query, string|int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter by updater.
     *
     * @param Builder $query
     * @param string|int $userId
     * @return Builder
     */
    public function scopeUpdatedBy(Builder $query, string|int $userId): Builder
    {
        return $query->where('updated_by', $userId);
    }

    /**
     * Scope: Filter by deleter.
     *
     * @param Builder $query
     * @param string|int $userId
     * @return Builder
     */
    public function scopeDeletedBy(Builder $query, string|int $userId): Builder
    {
        return $query->where('deleted_by', $userId);
    }

    /**
     * Scope: Filter models that have not been deleted (null deleted_by).
     * Note: This is different from softDeletes, it checks the deleted_by field.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        // Only apply this scope if the model actually has deleted_by column
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'deleted_by')) {
            return $query->whereNull('deleted_by');
        }

        // If no deleted_by column, return all records (not deleted in audit sense)
        return $query;
    }

    /**
     * Get the creator's name.
     *
     * @return string|null
     */
    public function getCreatorNameAttribute(): ?string
    {
        return $this->creator?->name;
    }

    /**
     * Get the updater's name.
     *
     * @return string|null
     */
    public function getUpdaterNameAttribute(): ?string
    {
        return $this->updater?->name;
    }

    /**
     * Get the deleter's name.
     *
     * @return string|null
     */
    public function getDeleterNameAttribute(): ?string
    {
        return $this->deleter?->name;
    }
}
