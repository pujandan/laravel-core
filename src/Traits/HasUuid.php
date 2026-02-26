<?php

namespace DaniarDev\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait
     *
     * @return void
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
