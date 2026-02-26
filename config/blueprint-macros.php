<?php

use Illuminate\Database\Schema\Blueprint;

/**
 * Blueprint Macros for Laravel Core
 *
 * This file contains custom macros for Laravel Blueprint
 * for audit fields and other common patterns
 */

Blueprint::macro('auditFields', function () {
    // created_by
    $this->foreignUuid('created_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete()
        ->index();

    // updated_by
    $this->foreignUuid('updated_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete()
        ->index();

    // deleted_by - only if softDeletes is used
    // Check if deleted_at column exists (must be called AFTER softDeletes())
    $columns = collect($this->getColumns())->pluck('name')->toArray();
    if (in_array('deleted_at', $columns)) {
        $this->foreignUuid('deleted_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index();
    }
});

Blueprint::macro('auditFieldsSafe', function () {
    // Safe version for existing tables - use try-catch to avoid errors
    try {
        $this->foreignUuid('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index();
    } catch (\Exception $e) {
        // Column exists, skip
    }

    try {
        $this->foreignUuid('updated_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index();
    } catch (\Exception $e) {
        // Column exists, skip
    }
});

Blueprint::macro('statusFields', function () {
    $this->boolean('is_active')->default(true)->index();
    $this->timestamp('activated_at')->nullable();
});