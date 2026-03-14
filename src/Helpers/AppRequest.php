<?php

namespace Daniardev\LaravelTsd\Helpers;

/**
 * Request helper for validation rules.
 */
class AppRequest
{
    /**
     * Get pagination validation rules.
     *
     * @param array $additionalRules Additional validation rules to merge
     * @return array
     */
    public static function pagination(array $additionalRules = []): array
    {
        return array_merge([
            'page' => ['nullable', 'integer'],
            'size' => ['nullable', 'integer'],
            'sort.by' => ['required_with:sort.direction', 'string'],
            'sort.direction' => ['required_with:sort.by', 'in:asc,desc'],
        ], $additionalRules);
    }
}
