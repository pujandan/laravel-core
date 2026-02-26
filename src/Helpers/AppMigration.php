<?php

namespace DaniarDev\LaravelCore\Helpers;



use Illuminate\Database\Schema\Blueprint;

class AppMigration {


    public static function useAddress(Blueprint $table)
    {
        $table->longText('address')->nullable()->default(null);
        $table->char('province_code', 2)->nullable();
        $table->foreign('province_code')->references('code')->on(AppHelper::provincesTable());
        $table->char('city_code', 4)->nullable();
        $table->foreign('city_code')->references('code')->on(AppHelper::citiesTable());
        $table->char('district_code', 7)->nullable();
        $table->foreign('district_code')->references('code')->on(AppHelper::districtsTable());
        $table->char('village_code', 10)->nullable();
        $table->foreign('village_code')->references('code')->on(AppHelper::villagesTable());
    }
}


