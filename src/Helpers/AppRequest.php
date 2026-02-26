<?php

namespace DaniarDev\LaravelCore\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Format Request.
 */
class AppRequest
{
    public static function pagination(array $data) : array
    {
        return array_merge([
            'page' => ['nullable', 'integer'],
            'size' => ['nullable', 'integer'],
            'sort.by' => ['required_with:sort.direction', 'string'],
            'sort.direction' => ['required_with:sort.by', 'in:asc,desc'],
        ], $data);
    }

}
