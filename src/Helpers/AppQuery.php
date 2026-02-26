<?php

namespace DaniarDev\LaravelCore\Helpers;

use App\Data\PaginationData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Format Request.
 */
class AppQuery
{
    /**
     * @param Builder $query
     * @param PaginationData $pagination
     * @return LengthAwarePaginator
     */
    public static function paginate(
        Builder $query,
        PaginationData $pagination
    ): LengthAwarePaginator {
        $sortBy = Str::snake($pagination->sortBy);
        $query->orderBy($sortBy, $pagination->direction);

        return $query->paginate(
            perPage: $pagination->size,
            page: $pagination->page
        );
    }


    public static function pagination(Builder $query, \Illuminate\Http\Request $request) : LengthAwarePaginator
    {
        // sorting
        $sortBy = $request->input('sort.by', 'created_at');
        $sortBy = AppHelper::isCamel($sortBy) ? Str::snake($sortBy) : $sortBy;
        $sortOrder = $request->input('sort.direction', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        // pagination
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        // return
        return $query->paginate(perPage: $size, page: $page);
    }


    public static function sort(Builder $query, \Illuminate\Http\Request $request) : Builder
    {
        // sorting
        $sortBy = $request->input('sort.by', 'created_at');
        $sortBy = AppHelper::isCamel($sortBy) ? Str::snake($sortBy) : $sortBy;
        $sortOrder = $request->input('sort.direction', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        return $query;
    }

}




//// sq
//$items = $model->getCollection();
//$page = $request->input('page', 1);
//$size = $request->input('size', 10);
//
//// Step 1: Group per number biar bisa sorting internal is_debt
//$grouped = $items->groupBy(fn($item) => (string) $item->number);
//
//// Step 2: Sort per group is_debt true dulu, lalu gabungkan ulang
//$sortedItems = collect();
//foreach ($grouped as $group) {
//    $sortedGroup = $group->sortBy([
//        ['is_debt', 'desc'],
//        ['id', 'asc'], // optional stabilizer
//    ]);
//    $sortedItems = $sortedItems->concat($sortedGroup->values());
//}
//
//// Step 3: Apply original sequence logic
//$sequence = ($page - 1) * $size;
//$numberSeen = [];
//$currentNumber = null;
//
//foreach ($sortedItems as $item) {
//    if ($item->number !== $currentNumber) {
//        $sequence++;
//        $currentNumber = $item->number;
//    }
//
//    $item->sequence = $sequence;
//
//    if (!isset($numberSeen[$item->number])) {
//        $item->sequence_number = $sequence;
//        $numberSeen[$item->number] = true;
//    } else {
//        $item->sequence_number = null;
//    }
//}
//
//$model->setCollection($sortedItems);
