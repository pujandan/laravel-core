<?php


namespace Daniardev\LaravelTsd\Traits;

use Daniardev\LaravelTsd\Helpers\AppValidation;
use Illuminate\Contracts\Validation\Validator;

trait AppRequestTrait
{

    /**
     * Handle failed validation.
     *
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        AppValidation::fail($validator);
    }

}
