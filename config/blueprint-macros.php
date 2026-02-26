<?php

use Illuminate\Database\Schema\Blueprint;

/**
 * Blueprint Macros for Laravel Core
 *
 * This file contains custom macros for Laravel Blueprint
 * for audit fields and other common patterns
 */

Blueprint::macro('auditFields', function () {
    $tableName = $this->getTable();

    // created_by
    $this->foreignUuid('created_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete()
        ->index("idx_{$tableName}_created_by");

    // updated_by
    $this->foreignUuid('updated_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete()
        ->index("idx_{$tableName}_updated_by");

    // deleted_by - only if softDeletes is used
    // Check if deleted_at column exists (must be called AFTER softDeletes())
    $columns = collect($this->getColumns())->pluck('name')->toArray();
    if (in_array('deleted_at', $columns)) {
        $this->foreignUuid('deleted_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index("idx_{$tableName}_deleted_by");
    }
});

Blueprint::macro('auditFieldsSafe', function () {
    $tableName = $this->getTable();

    // Safe version for existing tables - use try-catch to avoid errors
    try {
        $this->foreignUuid('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index("idx_{$tableName}_created_by");
    } catch (\Exception $e) {
        // Column exists, skip
    }

    try {
        $this->foreignUuid('updated_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->index("idx_{$tableName}_updated_by");
    } catch (\Exception $e) {
        // Column exists, skip
    }
});

Blueprint::macro('statusFields', function () {
    $tableName = $this->getTable();

    $this->boolean('is_active')->default(true)->index("idx_{$tableName}_is_active");
    $this->timestamp('activated_at')->nullable();
});