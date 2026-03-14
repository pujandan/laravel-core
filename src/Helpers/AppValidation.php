<?php

namespace Daniardev\LaravelTsd\Helpers;

// Application-specific import
// use App\Models\Passport;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AppValidation
{
    /**
     * Validate unique field with case-insensitive check.
     *
     * @param Builder $query Query builder instance
     * @param string $key Column name to check (will be properly escaped)
     * @param string $value Value to check
     * @param string|null $id ID to exclude from check
     * @param callable $fail Validation fail callback
     * @param string|null $attribute Custom attribute name for error message
     * @return void
     */
    public static function unique(Builder $query, string $key, string $value, ?string $id, callable $fail, ?string $attribute = null): void
    {
        $value = Str::lower($value);
        $grammar = $query->getGrammar();
        $query = $query->whereRaw("LOWER(" . $grammar->wrap($key) . ") = ?", [$value]);

        if ($id !== null) {
            $query->where('id', '!=', $id);
        }

        if ($query->exists()) {
            $fail(__('tsd_message.duplicate', ['attribute' => $attribute ?? __("tsd_label.$key")]));
        }
    }

    /**
     * Handle failed validation and throw HTTP exception.
     *
     * @param Validator $validator Validator instance
     * @return HttpResponseException
     */
    public static function fail(Validator $validator): HttpResponseException {
        $message = $validator->getMessageBag()->first();
        $count = $validator->getMessageBag()->count() - 1;
        $another = ((int)$count <= 0) ? '' : __('tsd_message.moreError', ['count' => $count]);
        throw new HttpResponseException(
            AppResponse::error($message . $another, 400, JsonResource::make($validator->getMessageBag()))
        );
    }


}
