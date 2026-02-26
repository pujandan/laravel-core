<?php


namespace DaniarDev\LaravelCore\Traits;

use App\Helpers\AppHelper;
use App\Helpers\AppValidation;
use Illuminate\Contracts\Validation\Validator;

trait AppRequestTrait
{

    /**
     * Get the validation data that applies to the request.
     *
     * @return array
     */
    public function validationData()
    {
        $data = AppHelper::toSnakeCase($this->all());
        $this->replace($data);
        return parent::validationData();
    }
    protected function failedValidation(Validator $validator)
    {
        AppValidation::fail($validator);
    }

}
