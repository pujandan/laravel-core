<?php

namespace DaniarDev\LaravelCore\Helpers;

class ValidationHelper
{
    /**
     * Get validation error messages
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    public static function getErrors($validator): array
    {
        $errors = [];
        foreach ($validator->errors()->toArray() as $field => $messages) {
            $errors[$field] = $messages[0];
        }
        return $errors;
    }

    /**
     * Format validation errors for API response
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    public static function formatErrors($validator): array
    {
        return [
            'message' => 'The given data was invalid.',
            'errors' => self::getErrors($validator),
        ];
    }
}
