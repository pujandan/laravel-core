<?php

namespace DaniarDev\LaravelCore\Helpers;


use App\Models\Passport;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class AppValidation
{
    public static function unique(Builder $query, $key, $value, $id, $fail, $attribute = null)
    {
        $value = Str::lower($value);
        $query = $query->whereRaw("LOWER({$key}) = ?", [$value]);
        if ($id) $query->where('id', '!=', $id);

        if ($query->exists()) {
            $fail(Lang::get('validation.duplicate', ['attribute' => $attribute ?? Lang::get("label.$key")]));
        }
    }

    public static function fail(Validator $validator) : HttpResponseException {
        $message = $validator->getMessageBag()->first();
        $count = $validator->getMessageBag()->count() - 1;
        $another = ((int)$count <= 0) ? '' : Lang::get('message.moreError', ['count' => $count]);
        throw new HttpResponseException(
            AppResponse::error($message . $another, 400, JsonResource::make($validator->getMessageBag()))
        );
    }


}
