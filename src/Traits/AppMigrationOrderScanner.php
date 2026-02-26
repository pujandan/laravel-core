<?php

namespace DaniarDev\LaravelCore\Traits;

use Illuminate\Support\Facades\File;

trait AppMigrationOrderScanner
{
    /**
     * Get migration order for tables by scanning migration files.
     * Automatically builds order from database/migrations directory.
     *
     * @return array Ordered list of table names
     */
    private function getMigrationOrder(): array
    {
        $migrationPath = database_path('migrations');
        $migrationFiles = File::glob($migrationPath . '/*_create_*.php');

        $tableOrder = [];

        foreach ($migrationFiles as $file) {
            $filename = basename($file, '.php');

            // Extract timestamp from filename (format: YYYY_MM_DD_HHMMSS)
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $filename, $matches)) {
                $timestamp = $matches[1];

                // Extract table name from migration file content
                $tableName = $this->extractTableName($file);

                if ($tableName) {
                    // Use timestamp as key to maintain order, table name as value
                    $tableOrder[$timestamp] = $tableName;
                }
            }
        }

        // Sort by timestamp (key)
        ksort($tableOrder);

        return array_values($tableOrder);
    }

    /**
     * Extract table name from migration file.
     *
     * @param string $filePath
     * @return string|null
     */
    private function extractTableName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Match Schema::create('table_name', ...) or Schema::create("table_name", ...)
        if (preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}