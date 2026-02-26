<?php
namespace DaniarDev\LaravelCore\Traits;

use App\Data\PaginationData;
use Illuminate\Http\Request;

trait AppPagination
{
    protected function pagination(Request $request): PaginationData
    {
        return new PaginationData(
            sortBy: $request->input('sort.by', 'created_at'),
            direction: $request->input('sort.direction', 'desc'),
            page: (int) $request->input('page', 1),
            size: (int) $request->input('size', 10),
        );
    }


}
