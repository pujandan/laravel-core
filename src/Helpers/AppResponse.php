<?php

namespace DaniarDev\LaravelCore\Helpers;

use App\Enums\CategoryAccountType;
use App\Enums\IdentitySelection;
use App\Enums\SelectionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Format response.
 */
class AppResponse
{

    /**
     * Give success response.
     */
    public static function success(?JsonResource $data, ?string $message = null): JsonResponse
    {
        // Buat response array baru setiap request (tidak pakai static)
        $response = [
            'code' => 200,
            'message' => $message,
            'data' => null,
        ];

        $array = null;
        if ($data != null) {
            $array = $data->toArray(new Request());
        }

        // Wrap data
        if (isset($array['data'])) {
            // Merge dengan data yang sudah ada pagination
            $response = array_merge($response, $array);
        } else {
            $response['data'] = $array;
        }

        // Remove data key jika null
        if ($array === null) {
            unset($response['data']);
        }

        return response()->json($response, $response['code']);
    }

    /**
     * Give error response.
     */
    public static function error(?string $message = null, int $code = 404, ?JsonResource $error = null): JsonResponse
    {
        // Buat response array baru setiap request
        $response = [
            'code' => $code,
            'message' => $message,
        ];

        // Tambahkan error jika ada
        if ($error !== null) {
            $response['error'] = $error;
        }

        return response()->json($response, $response['code']);
    }

    /**
     * Simple print response.
     */
    public static function print(?string $message, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Selection response from Enums.
     */
    public static function selectionEnums(string $enumClass, string $key, ?string $type = null): Collection
    {
        $cases = $enumClass::cases();
        $types = collect([]);

        foreach ($cases as $item) {
            $label = null;

            // get label by enum type
            if ($enumClass === IdentitySelection::class or $enumClass === CategoryAccountType::class) {
                $label = $item->label();
            }

            $types->push([
                'id' => $item,
                'label' => $label,
            ]);
        }

        return self::selection($types, $key, 'label', $type);
    }

    /**
     * Format collection as selection dropdown.
     */
    public static function selection(Collection $items, string $key, string $value, ?string $type = null): Collection
    {
        $data = collect([]);

        // Add "All" option
        $data->add([
            'id' => null,
            'label' => \Lang::get('label.all'),
        ]);

        // Add items
        foreach ($items as $item) {
            $data->add([
                'id' => $item['id'],
                'label' => $item[$value],
            ]);
        }

        $selections = collect([]);
        $selections->add(collect([
            'key' => $key,
            'type' => $type ?? SelectionType::DROPDOWN,
            'values' => $data,
        ]));

        return $selections;
    }
}
