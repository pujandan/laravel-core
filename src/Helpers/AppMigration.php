<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Database\Schema\Blueprint;

class AppMigration
{
    /**
     * Add address columns to table.
     *
     * Adds generic address fields. For region-specific fields (province, city, etc.),
     * you can add them manually in your migration.
     *
     * @param Blueprint $table Table blueprint
     * @return void
     */
    public static function useAddress(Blueprint $table): void
    {
        $table->longText('address')->nullable();

        // Note: For region-specific fields (province_code, city_code, etc.),
        // add them manually in your migration with proper foreign key constraints
        // Example:
        // $table->char('province_code', 2)->nullable();
        // $table->foreign('province_code')->references('code')->on('provinces');
    }
}
