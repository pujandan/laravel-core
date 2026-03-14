<?php

namespace Daniardev\LaravelTsd\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerAuditFieldsMacro();
    }

    /**
     * Register custom auditFields macro for Blueprint.
     *
     * This allows usage like $table->auditFields(); in migrations,
     * similar to  $table->timestamps(6);
     *
     * @return void
     */
    protected function registerAuditFieldsMacro(): void
    {
        Blueprint::macro('auditFields', function () {
            /** @var Blueprint $this */
            $table = $this;

            // Add created_by and updated_by (always present)
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            // Add deleted_by only if softDeletes is detected
            // Check if 'deleted_at' column exists in this table
            $hasSoftDeletes = collect($table->getColumns())->contains(function ($column) {
                return $column->get('name') === 'deleted_at' ||
                       $column->get('type') === 'softDeletes';
            });

            if ($hasSoftDeletes) {
                $table->uuid('deleted_by')->nullable();
            }

            // Add foreign keys (only if users table exists)
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');

                $table->foreign('updated_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');

                if ($hasSoftDeletes) {
                    $table->foreign('deleted_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                }
            }

            // Add indexes for performance
            $table->index('created_by');
            $table->index('updated_by');

            if ($hasSoftDeletes) {
                $table->index('deleted_by');
            }
        });

        Blueprint::macro('auditFieldsSafe', function () {
            /** @var Blueprint $this */
            $table = $this;

            // Get table name
            $tableName = $table->getTable();

            // Check existing columns using Schema facade
            $hasCreatedBy = Schema::hasColumn($tableName, 'created_by');
            $hasUpdatedBy = Schema::hasColumn($tableName, 'updated_by');
            $hasDeletedAt = Schema::hasColumn($tableName, 'deleted_at');
            $hasDeletedBy = Schema::hasColumn($tableName, 'deleted_by');

            // Add created_by if not exists
            if (!$hasCreatedBy) {
                $table->uuid('created_by')->nullable();
            }

            // Add updated_by if not exists
            if (!$hasUpdatedBy) {
                $table->uuid('updated_by')->nullable();
            }

            // Add deleted_by if softDeletes exists and deleted_by doesn't exist
            if ($hasDeletedAt && !$hasDeletedBy) {
                $table->uuid('deleted_by')->nullable();
            }

            // Add foreign keys and indexes for newly added columns (only if users table exists)
            if (Schema::hasTable('users')) {
                if (!$hasCreatedBy) {
                    $table->foreign('created_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                    $table->index('created_by');
                }

                if (!$hasUpdatedBy) {
                    $table->foreign('updated_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                    $table->index('updated_by');
                }

                if ($hasDeletedAt && !$hasDeletedBy) {
                    $table->foreign('deleted_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                    $table->index('deleted_by');
                }
            }
        });
    }
}