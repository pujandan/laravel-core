<?php

namespace DaniarDev\LaravelCore\Helpers;

use App\Enums\AccountHeaderCategory;
use App\Enums\AccountHeaderType;
use App\Enums\LogType;
use App\Enums\PackageRefundType;
use App\Enums\PackageStatus;
use App\Enums\PackageType;
use App\Enums\PointTypeEnum;
use App\Exceptions\AppException;
use App\Http\Resources\Api\Finance\Report\ReportAccountHeaderCollection;
use App\Models\AdditionalCost;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\BankAccount;
use App\Models\BankCode;
use App\Models\CodeAccount;
use App\Models\Currency;
use App\Models\Employment;
use App\Models\Hotel;
use App\Models\Identity;
use App\Models\Journal;
use App\Models\Log;
use App\Models\Marketing;
use App\Models\MarketingReward;
use App\Models\MarketingTag;
use App\Models\Office;
use App\Models\Package;
use App\Models\Passport;
use App\Models\Payment;
use App\Models\Point;
use App\Models\Reference;
use App\Models\ReportAccountHeader;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\ScheduleFlight;
use App\Models\ScheduleVacation;
use App\Models\Season;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class AppHelper {

    public static function provincesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'provinces';
    }

    public static function citiesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'cities';
    }

    public static  function districtsTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'districts';
    }

    public static function villagesTable() : string
    {
        return config('laravolt.indonesia.table_prefix').'villages';
    }

    public static function replaceSpace(string $data) : string
    {
        return preg_replace('/\s/', '', $data);
    }

    public static function toAz09(string $value) : string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $value);
    }

    public static function formatIdentityForm(array $data) : array
    {
        $data['phone_number'] = self::replaceSpace($data['phone_number']);
        $data['name'] = Str::title($data['name']);
        $data['father_name'] = Str::title($data['father_name']);
        $data['birth_place'] = Str::title($data['birth_place']);
        $data['address'] = Str::upper($data['address']);
        Arr::forget($data, 'is_passport');
        return $data;
    }


    /**
     * @param $nominal
     * @param string $prefix
     * @param int $decimal
     * @param string $separator
     * @param string $thousand
     * @param bool $isParentheses
     * @return string
     */
    public static function formatCurrency(
        $nominal,
        string $prefix = '',
        int $decimal = 0,
        string $separator = ',',
        string $thousand = '.',
        bool $isParentheses = false // with ()
    ) : string {
        // Hilangkan semua karakter kecuali angka, koma, titik, dan minus
        $nominal = preg_replace('/[^\d,.-]/', '', $nominal);

        // Ganti koma terakhir jadi titik supaya bisa dikonversi ke float
        if (str_contains($nominal, ',')) {
            $nominal = preg_replace('/,(\d{1,2})$/', '.$1', $nominal);
        }

        $nominal = (float)$nominal;

        // Format angka
        $formatted = number_format(abs($nominal), $decimal, $separator, $thousand);

        // Gabungkan prefix kalau ada
        $result = trim("{$prefix} {$formatted}");

        if ($nominal < 0) {
            return $isParentheses ? "({$result})" : "-{$result}";
        } else {
            return $result;
        }
    }



    public static function arrivalDate(?string $departureDate, ?int $seat) : ?string
    {
        if($departureDate!==null && $seat !== null){
            return Carbon::parse((string)$departureDate)->addDays((int)$seat - 1);
        }
        return null;
    }

    public static function formatDate($date, $format = 'd F Y') : string
    {
        Carbon::setLocale('id');
        return Carbon::parse($date)->translatedFormat($format);
    }

    public static function counted($amount, $lang = 'id') : string
    {
        $amount = (int) $amount;

        if ($amount == 0) {
            return __('label.zero', [], $lang) . ' ' . __('label.rupiah', [], $lang);
        }

        $result = self::countedRecursive($amount, $lang);
        return ucfirst(trim($result)) . ' ' . __('label.rupiah', [], $lang);
    }

    private static function countedRecursive($amount, $lang) : string
    {
        if ($amount == 0) {
            return '';
        }

        $result = '';

        // Trillions
        if ($amount >= 1000000000000) {
            $trillion = floor($amount / 1000000000000);
            $result .= ($lang == 'id' ? ($trillion == 1 ? 'se' : '') : '') . self::countedRecursive($trillion, $lang) . ' ' . __('label.trillion', [], $lang) . ' ';
            $amount %= 1000000000000;
        }

        // Billions
        if ($amount >= 1000000000) {
            $billion = floor($amount / 1000000000);
            $result .= ($lang == 'id' ? ($billion == 1 ? 'se' : '') : '') . self::countedRecursive($billion, $lang) . ' ' . __('label.billion', [], $lang) . ' ';
            $amount %= 1000000000;
        }

        // Millions
        if ($amount >= 1000000) {
            $million = floor($amount / 1000000);
            $result .= ($lang == 'id' ? ($million == 1 ? 'se' : '') : '') . self::countedRecursive($million, $lang) . ' ' . __('label.million', [], $lang) . ' ';
            $amount %= 1000000;
        }

        // Thousands
        if ($amount >= 1000) {
            $thousand = floor($amount / 1000);
            $result .= ($lang == 'id' ? ($thousand == 1 ? 'se' : '') : '') . self::countedRecursive($thousand, $lang) . ' ' . __('label.thousand', [], $lang) . ' ';
            $amount %= 1000;
        }

        // Hundreds
        if ($amount >= 100) {
            $hundred = floor($amount / 100);
            $result .= ($lang == 'id' ? ($hundred == 1 ? 'se' : '') : '') . self::countedRecursive($hundred, $lang) . ' ' . __('label.hundred', [], $lang) . ' ';
            $amount %= 100;
        }

        // 1-99
        if ($amount > 0) {
            if ($lang == 'id') {
                $result .= self::terbilangIndonesian($amount);
            } else {
                $result .= self::terbilangEnglish($amount);
            }
        }

        return $result;
    }

    private static function terbilangIndonesian($amount) : string
    {
        $result = '';

        if ($amount >= 1 && $amount <= 11) {
            $words = [
                1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan',
                10 => 'sepuluh', 11 => 'sebelas'
            ];
            $result = $words[$amount] . ' ';
        } elseif ($amount >= 12 && $amount <= 19) {
            $units = $amount - 10;
            $unitWords = [
                2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
            ];
            $result = $unitWords[$units] . ' belas ';
        } elseif ($amount >= 20 && $amount <= 99) {
            $tens = floor($amount / 10);
            $unit = $amount % 10;

            $tensWords = [
                2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
            ];

            $result = $tensWords[$tens] . ' puluh ';

            if ($unit > 0) {
                $unitWords = [
                    1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
                    6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan'
                ];
                $result .= $unitWords[$unit] . ' ';
            }
        }

        return $result;
    }

    private static function terbilangEnglish($amount) : string
    {
        $result = '';

        if ($amount >= 1 && $amount <= 19) {
            $words = [
                1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
                10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
                14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
                18 => 'eighteen', 19 => 'nineteen'
            ];
            $result = $words[$amount] . ' ';
        } elseif ($amount >= 20 && $amount <= 99) {
            $tens = floor($amount / 10);
            $unit = $amount % 10;

            $tensWords = [
                2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
                6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
            ];

            $result = $tensWords[$tens] . ' ';

            if ($unit > 0) {
                $unitWords = [
                    1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                    6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine'
                ];
                $result .= $unitWords[$unit] . ' ';
            }
        }

        return $result;
    }


    public static function getPackageID(?string $createdAt = null) : string
    {
        // 240000001
        if($createdAt !== null){
            $carbonDate = Carbon::createFromFormat('Y-m-d', $createdAt);
            $year = $carbonDate->isoFormat('YY');
        }else{
            $year = Carbon::now()->isoFormat('YY');
        }

        $last =  Package::select(
                    DB::raw('RIGHT(registered_number, 7) as last'),
                )
                ->where(DB::raw('LEFT(registered_number, 2)'), '=', "$year")
                ->orderBy('registered_number', 'desc')
                ->first();


        if($last){
            (int)$number = (int)$last->last + 1;
            return $year.str_pad((string)$number, 7, '0', STR_PAD_LEFT);
        }else{
            return $year.str_pad("1", 7, '0', STR_PAD_LEFT);
        }
    }


    /**
     * Generate increment number with race-condition protection.
     *
     * Format: YY + CODE + 7 digit (e.g., 24PY0000001)
     *
     * WARNING: This method uses lockForUpdate() and MUST be called within DB::transaction
     * to prevent race condition and ensure data consistency.
     *
     * @param Model $model Eloquent model class
     * @param string|null $code Optional code prefix (e.g., 'PY' for payment, 'JR' for journal)
     * @return string Generated number
     *
     * @throws RuntimeException if called outside DB::transaction
     *
     * @example
     *      DB::transaction(function () {
     *          $number = AppHelper::getIncrement(Payment::class, 'PY');
     *          // Create record with $number
     *      });
     */
    public static function getIncrement(Model $model, ?string $code) : string
    {
        // validation must be within transaction
        if (DB::transactionLevel() === 0) {
            throw new LogicException(
                Lang::get('message.mustUseTransaction'),
                500
            );
        }

        // 24PY0000001
        $year = Carbon::now()->isoFormat('YY');
        $year = ($code != null) ? $year.$code : $year;
        $codeLength = strlen($code) + 2;

        // use lockForUpdate to prevent race condition
        $last = $model::select(DB::raw('RIGHT(number, 7) as last'))
            ->where(DB::raw("LEFT(number, $codeLength)"), $year)
            ->lockForUpdate()
            ->orderBy('last', 'desc')
            ->first();

        if($last){
            $number = (int)$last->last + 1;
            return $year.str_pad((string)$number, 7, '0', STR_PAD_LEFT);
        }else{
            return $year.str_pad("1", 7, '0', STR_PAD_LEFT);
        }
    }

    /**
     * DEPRECATED - Use MarketingService::generateMarketingNumber() instead.
     *
     * This method is kept for backward compatibility only.
     *
     * @deprecated
     */
    public static function getMarketingID(?string $identityId, ?string $joinedAt = null) : ?string
    {
        $identity = Identity::find($identityId);
        if($identity)
        {
            // get year in
            if($joinedAt !== null){
                $carbonDate = Carbon::createFromFormat('Y-m-d', $joinedAt);
                $year = $carbonDate->isoFormat('YY');
            }else{
                $year = Carbon::now()->isoFormat('YY');
            }

            // get initial name (2 digits)
            $name = self::getInitialName($identity->name);
            // get last counted by year and  name
            $marketing = Marketing::withTrashed()->select(
                DB::raw('RIGHT(marketing_number, 2) as last'),
                DB::raw('LEFT(marketing_number, 4) as prefix'),
                )
                ->where(DB::raw('LEFT(marketing_number, 4)'), '=', "$year$name")
                ->orderBy('marketing_number', 'desc')
                ->first();
            // formatting to year name counted
            $last = ($marketing != null) ? (int)$marketing->last + 1 : 1;
            $format2digits = str_pad((string)$last, 2, '0', STR_PAD_LEFT);
            return "$year$name$format2digits";
        }else{
            throw new AppException(Lang::get('message.failedGenerateIdMarketing'), 422);
        }
    }

    public static function getInitialName(?string $name) : string
    {
        // convert name to array by space
        $words = explode(' ', $name ?? '');
        // if the name consists of only one word, return the first letter of the word
        if (count($words) === 1) {
            return strtoupper(substr($name, 0, 2));
        }
        // initialize string to store initials
        $initials = '';
        // loop through each word
        foreach ($words as $word) {
            // take the first character of each word
            $initials .= strtoupper(substr($word, 0, 1));
            // if you have reached 2 letters, stop the iteration
            if (strlen($initials) >= 2) {
                break;
            }
        }
        // return the resulting initials
        return $initials;
    }

    // api
    public static function toSnakeCase(array $data): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $snakeKey = Str::snake($key);
            if (is_array($value)) {
                return [$snakeKey => self::toSnakeCase($value)];
            } else {
                return [$snakeKey => $value];
            }
        })->toArray();
    }


    public static function toCamelCase(array $data): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $camelKey = Str::camel($key);
            if (is_array($value)) {
                return [$camelKey => self::toCamelCase($value)];
            } else {
                return [$camelKey => $value];
            }
        })->toArray();
    }


    public static function enumToArray(string $enum) : array
    {
        try {
            $reflectionClass = new \ReflectionClass($enum);
            $officeTypes = $reflectionClass->getConstants();
            return array_values($officeTypes);
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    public static function enumToImplode(string $enum, string $separator = ',') : string
    {
        return implode($separator, self::enumToArray($enum));
    }

    public static function arrayMerge(...$arrays) : array
    {
        $mergedArray = [];
        foreach ($arrays as $array) {
            $mergedArray = array_merge($mergedArray, $array);
        }
        return $mergedArray;
    }

    public static function isCamel(?string $value) : bool
    {
        if (Str::camel($value) === $value) {
            return true;
        } else {
            return false;
        }
    }

    public static function toBoolean($value) : bool
    {
        if($value == 1 || $value == "1"){
            return true;
        }else{
            return false;
        }
    }

    public static function assetStorage(?string $image) : ?string
    {
        return $image ? asset("storage/{$image}") : null;
    }

    public static function ifNull($data, $replace = null)
    {
        return $data != null ? $data : $replace ?? null;
    }

    /**
     * @throws AppException
     */
    public static function generateCodeAccount(string $parentId, ?bool $isDebt = null) : ?CodeAccount
    {
        $parent = CodeAccount::find($parentId);
        if($parent !== null && $parent->is_addition && ($parent->level == 1 || $parent->level == 2)) {

            if ($parent->level === 1 && $isDebt === null) {
                throw new AppException(Lang::get('message.requiredOperation'), 400);
            } else {
                $children = CodeAccount::where('parent_id', $parentId)->orderBy('code', 'desc')->first();

                $newCode = ($parent->level === 1) ? 10 : 1;
                $isDebt = ($parent->level === 1) ? $isDebt : $parent->is_debt;
                $level = $parent->level + 1;
                $isAddition = $parent->level === 1;

                if ($children !== null) {
                    $codes = explode('.', $children->code);
                    $newCode = (int)end($codes) + 1;
                }
                $newCode = str_pad($newCode, 2, '0', STR_PAD_LEFT);
                $newCode = $parent->code.'.'.$newCode;

                return new CodeAccount([
                    'code' => $newCode,
                    'is_debt' => $isDebt,
                    'level' => $level,
                    'is_addition' => $isAddition,
                    'parent_id' => $parentId,
                ]);
            }

        }else{
            throw new AppException(Lang::get('message.requiredParentCoa'), 400);
        }
    }

    /**
     * @param string $date
     * @return Season
     */
    public static function findSeason(string $date): Season
    {
        return Season::where('first_date', '<=', $date)
            ->where('last_date', '>=', $date)
            ->orderBy('first_date', 'desc')
            ->firstOrFail();
    }

    public static function getPPHCorp() : float
    {
        $reference = Reference::where('code', 'refPPHCorp')->first();
        return (float)$reference?->value ?? 0;
    }

    public static function getPPH23() : float
    {
        $reference = Reference::where('code', 'refPPH23')->first();
        return (float)$reference?->value ?? 0;
    }

    public static function getClass(object $object) : string
    {
        return get_class($object);
    }
    public static function getClassName(object $object) : string
    {
        return class_basename($object);
    }

    public static function certificationDate($departure_date, $arrival_date)
    {
        try {
            Carbon::setLocale('id');
            $departure = Carbon::parse($departure_date);
            $arrival = Carbon::parse($arrival_date);

            $departure_day = $departure->format('j');
            $arrival_day = $arrival->format('j');
            $departure_month = $departure->translatedFormat('F');
            $arrival_month = $arrival->translatedFormat('F');
            $departure_year = $departure->format('Y');
            $arrival_year = $arrival->format('Y');

            $departure_format = $departure_day;
            $arrival_format = $arrival_day . ' ' . $arrival_month . ' ' . $arrival_year;

            if ($departure_year != $arrival_year) {
                $departure_format .= ' ' . $departure_month . ' ' . $departure_year;
            } elseif ($departure_month != $arrival_month) {
                $departure_format .= ' ' . $departure_month;
            }

            return $departure_format . ' s/d ' . $arrival_format;

        } catch (AppException|\Exception $e) {
            return $e->getMessage();
        }
    }

    static function pointLabel(int $point) : string
    {
        if($point == 0){
            return '';
        }else
        if ($point > 0) {
            return "+$point";
        }else{
            return $point;
        }
    }

    /**
     * @throws AppException
     */
    public static function getReference(?string $code) : ?string
    {
        $reference = Reference::where('code', $code)->first();
        if($reference == null) throw new AppException(Lang::get('message.emptyReference', ['code' => $code]), 400);
        return $reference->value;
    }

    /**
     * @param string|null $className
     * @return string
     */
    public static function classToName(?string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);
        return strtolower($shortName);
    }


    /**
     * @param string $codeAccountId
     * @param bool $isDebtJournal
     * @param float $amount
     * @return void
     * @throws AppException
     */
    public static function reBalance(string $codeAccountId, bool $isDebtJournal, float $amount) : void
    {
        $codeAccount = CodeAccount::find($codeAccountId);

        if($codeAccount == null) throw new AppException(Lang::get('message.emptyLoadedName', ['name' => Lang::get('label.lbCodeAccount')]), 404);
        if($codeAccount->is_debt === null) throw new AppException("Normal journal type [$codeAccount->name] is null.", 422);

        $isDebtAccount = (bool)$codeAccount->is_debt;

        if($isDebtAccount && $isDebtJournal || !$isDebtAccount && !$isDebtJournal) {
            // add
            // D -> D | K -> K (+)
            $codeAccount->addBalance($amount);
        }else if($isDebtAccount && !$isDebtJournal || !$isDebtAccount && $isDebtJournal) {
            // min
            // D -> C | C -> D (-)
            $codeAccount->minBalance($amount);
        }else{
            throw new AppException('Calculation balance account invalid. .', 422);
        }
    }

    public static function getAccountBalances(string $date)
    {
        \DB::enableQueryLog();
        $query = "
                WITH LatestTransactions AS (
                SELECT
                    IF(jr.is_deposit, ca.deposit_account_id, ca.code_account_id) AS code_account_id,
                    jr.balance,
                    ROW_NUMBER() OVER (
                        PARTITION BY IF(jr.is_deposit, ca.deposit_account_id, ca.code_account_id)
                        ORDER BY jr.date DESC, jr.number DESC
                        ) AS rn
                FROM
                    code_accounts as coa
                        LEFT JOIN category_accounts as ca ON coa.id = ca.code_account_id
                        LEFT JOIN journals as jr ON jr.category_account_id = ca.id
                WHERE
                    DATE(jr.date) <= :date
                AND
                    jr.is_balance = true
            )
            SELECT
                coa.id AS id,
                COALESCE(LT.balance, 0) AS balance
            FROM
                code_accounts coa
                    LEFT JOIN LatestTransactions LT ON coa.id = LT.code_account_id AND LT.rn = 1
            ORDER BY
                coa.id;
            ";
        $results = DB::select($query, ['date' => $date]);
        return collect($results);
    }

    public static function getUpdateBalances(Collection $reports, Collection $balances) : Collection
    {
        return $reports->map(function ($report) use ($balances) {
            $accounts = collect($report->accounts)->toArray();
            $details = collect($report->details)->map(function ($detail) use ($balances) {
                $codeAccount = $detail->codeAccount;
                $codeAccount['name'] = $detail->name ?? $codeAccount->name;
                return $codeAccount;
            })->toArray();

            $data = array_merge($accounts, $details);


            $report['data'] = collect($data)->map(function ($item) use ($balances) {
                $balance = $balances[$item['id']] ?? 0;
                // $item['balance'] = (float)$balance;
                $item['balance'] = (float) ($item['is_reverse'] ? $balance * -1 : $balance);
                return $item;
            });
            $report['total'] = collect($report['data'])->sum('balance');
            unset($report['details']);
            unset($report['accounts']);

            return $report;
        });
    }


    /**
     * @param string $marketingId
     * @return void
     */
    public static function updatePointMarketing(string $marketingId): void
    {
        $points = Point::select([
            'date',
            DB::raw("IF(MAX(CASE WHEN type = 'usage' THEN 1 ELSE 0 END) = 1, 'usage', 'closing') AS type"),
            DB::raw('SUM(point) AS balance'),
            DB::raw('SUM(SUM(point)) OVER (ORDER BY date, type) AS point')
        ])
            ->where('marketing_id', $marketingId)
            ->groupBy('date')->get();

        if (!$points->isEmpty()) {
            $lastPoint = $points->last();
            $pointBalance = $lastPoint->point;
        } else {
            $pointBalance = 0;
        }

        $update = Marketing::find($marketingId);
        $update->point_balance = $pointBalance;
        $update->save();
    }


    /**
     * @param array $types
     * @param array $categories
     * @param string|null $date
     * @return Collection
     */
    public static function getBalances(
        array $types,
        array $categories,
        ?string $date = null,
    ): Collection
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        $balances = AppHelper::getAccountBalances($date)->pluck('balance', 'id');

        $accounts = ReportAccountHeader::with(['details', 'details.codeAccount', 'accounts'])
            ->whereIn('type', $types)
            ->whereIn('category', $categories)
            ->orderBy('type', 'desc')
            ->orderBy('index')
            ->get();

        return AppHelper::getUpdateBalances($accounts, $balances);
    }


    /**
     * @throws AppException
     */
    public static function onCheckBalance(string $date)
    {
        $balances = AppHelper::getBalances(
            types: [
                // profit and loss
                AccountHeaderType::PROFIT_LOSS,
                // balance sheet
                AccountHeaderType::BALANCE_SHEET
            ],
            categories: [
                // profit and loss
                AccountHeaderCategory::INCOMES,
                AccountHeaderCategory::COSTS,
                // balance sheet
                AccountHeaderCategory::CURRENT_ASSETS,
                AccountHeaderCategory::FIXED_ASSETS,
                AccountHeaderCategory::PASSIVE
            ],
            date: $date,
        );

        /* calculate profit and loss */
        $totalIncomes = collect(new ReportAccountHeaderCollection(
            $balances->where('category', AccountHeaderCategory::INCOMES),
        ))->sum(fn($income) => $income['is_increase'] ? $income['total'] : -$income['total']);

        $totalCosts = collect(new ReportAccountHeaderCollection(
            $balances->where('category', AccountHeaderCategory::COSTS),
        ))->sum(fn($income) => $income['is_increase'] ? $income['total'] : -$income['total']);

        $totalProfitLoss = $totalIncomes - $totalCosts;
        $idProfitAndLoss = AppHelper::getReference('refCurrentProfitAndLoss');;
        /* end calculate profit and loss */

        $balanceSheet = $balances->where('type', AccountHeaderType::BALANCE_SHEET)
            ->whereIn('category', [
                AccountHeaderCategory::CURRENT_ASSETS,
                AccountHeaderCategory::FIXED_ASSETS,
                AccountHeaderCategory::PASSIVE
            ]);

        $currentAssets = new ReportAccountHeaderCollection(
            $balanceSheet->where('category', AccountHeaderCategory::CURRENT_ASSETS),
        );
        $totalCurrentAssets = collect($currentAssets)->sum(fn($e) => $e['is_increase'] ? $e['total'] : -$e['total']);

        $fixedAssets = new ReportAccountHeaderCollection(
            $balanceSheet->where('category', AccountHeaderCategory::FIXED_ASSETS),
        );
        $totalFixedAssets = collect($fixedAssets)->sum(fn($e) => $e['is_increase'] ? $e['total'] : -$e['total']);

        $passives = collect(new ReportAccountHeaderCollection(
            $balanceSheet->where('category', AccountHeaderCategory::PASSIVE),
        ));
        $passives = collect($passives)->map(function ($item) use ($totalProfitLoss, $idProfitAndLoss) {
            $data = collect($item['data'])->map(function ($e) use ($totalProfitLoss, $idProfitAndLoss) {
                if($e['id'] == $idProfitAndLoss){
                    $e['balance'] = $totalProfitLoss;
                }
                return $e;
            });
            $item['data'] =  $data;
            $item['total'] = collect($data)->sum('balance');
            return $item;
        })->values();

        $totalPassives = collect($passives)->sum(fn($e) => $e['is_increase'] ? $e['total'] : -$e['total']);

        /* check balance */
        if ($totalCurrentAssets+$totalFixedAssets != $totalPassives) {
            $actives = self::formatCurrency(($totalCurrentAssets+$totalFixedAssets), isParentheses: true);
            $passives = self::formatCurrency($totalPassives, isParentheses: true);
            throw new AppException("Neraca tidak sesuai: Aktiva $actives & Pasiva $passives");
        }
    }

    /**
     * @throws AppException
     */
    public static function updateBalanceProfitLoss(float $balance) : string
    {
        // update profit and loss in account
        $profitAndLoss = AppHelper::getReference('refCurrentProfitAndLoss');
        $account = CodeAccount::find($profitAndLoss);
        if($account == null) throw new AppException(Lang::get('message.emptyLoadedName', ['name' =>  Lang::get('label.lbCodeAccount')]));
        $account->balance = $balance;
        $account->save();
        return $profitAndLoss;
    }

    /**
     * @throws AppException
     */
    public static function validatePackageStatus(PackageStatus $status, bool $withDebt = true): void
    {
        // if($status === PackageStatus::DRAFT) throw new AppException(__('message.packageStatusDraft'), 422);
        if($withDebt && $status === PackageStatus::DEBT) throw new AppException(__('message.packageStatusDebt'), 422);
        if($status === PackageStatus::SUCCESS) throw new AppException(__('message.packageStatusSuccess'), 422);
        if($status === PackageStatus::CANCEL) throw new AppException(__('message.packageStatusCancel'), 422);
    }

    /**
     * @throws AppException
     */
    public static function checkAdditionalCostStatus(?Model $package, ?string $id, ?string $type, ?float $total): ?AdditionalCost
    {
        if($type == PackageRefundType::ADDITIONAL_COST->value && $id != null) {
            // for additional cost
            $ac = AdditionalCost::find($id);
            if($ac == null) throw new AppException(Lang::get('message.emptyLoadedName', ['name' => Lang::get('label.additionalCosts')]), 404);

            $remaining = $ac->paid - $ac->amount; // remaining to refund
            if($total > $remaining) throw new AppException('Nominal pengembalian biaya tambahan tidak mencukupi.', 422);

            return $ac;
        } else if($type == PackageRefundType::PACKAGE->value) {
            // check package remaining to return
            $packagePaid = AppHelper::getPackagePaid($package->id);
            if($packagePaid['remaining'] <= 0) throw new AppException('Nominal pengembalian paket tidak mencukupi.', 422);
        } else {
            throw new AppException('Tipe transaksi tidak untuk pengembalian dana.', 422);
        }
        return null;
    }


    public static function getPackagePaid(string $id) : array
    {
        // Subquery for freebie
        $freebieSub = DB::table('additional_costs')
            ->select('transable_id',
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(paid) as paid'))
            ->where('transable_type', 'App\\Models\\Package')
            ->where('is_freebie', true)
            ->groupBy('transable_id');

        // Subquery for non-freebie
        $nonFreebieSub = DB::table('additional_costs')
            ->select('transable_id',
                DB::raw('SUM(paid) as paid'))
            ->where('transable_type', 'App\\Models\\Package')
            ->where('is_freebie', false)
            ->groupBy('transable_id');

        $data = DB::table('packages as p')
            ->leftJoinSub($freebieSub, 'freebie', function ($join) {
                $join->on('freebie.transable_id', '=', 'p.id');
            })
            ->leftJoinSub($nonFreebieSub, 'non_freebie', function ($join) {
                $join->on('non_freebie.transable_id', '=', 'p.id');
            })
            ->where('p.id', $id)
            ->select([
                'p.id',
                DB::raw('
                    COALESCE(p.total_package, 0)
                    - COALESCE(freebie.amount, 0)
                    - COALESCE(p.discount, 0)
                    - COALESCE(p.discount_marketing, 0) as package_total
                '),
                DB::raw('
                    COALESCE(p.paid, 0)
                    - COALESCE(non_freebie.paid, 0)
                    - COALESCE(freebie.paid, 0) as package_paid
                '),
                DB::raw('
                    (
                        COALESCE(p.paid, 0)
                        - COALESCE(non_freebie.paid, 0)
                        - COALESCE(freebie.paid, 0)
                    ) - (
                        COALESCE(p.total_package, 0)
                        - COALESCE(freebie.amount, 0)
                        - COALESCE(p.discount, 0)
                        - COALESCE(p.discount_marketing, 0)
                    ) as remaining
                '),
            ])
            ->first();

        return (array)$data;
    }


    /**
     * Deprecated => Move to Service
     * @param PackageType $type
     * @return bool
     */
    static function isFreePayment(PackageType $type): bool
    {
        return $type == PackageType::FREE_OWNER || $type == PackageType::FREE_MARKETING || $type == PackageType::LEADER_OWNER || $type == PackageType::LEADER_MARKETING;
    }

    /**
     * @param bool $isDebtJournal
     * @param bool $isDebtAccount
     * @param float $amount
     * @return float
     * @throws AppException
     */
    public static function getBalanceOperation(bool $isDebtJournal, bool $isDebtAccount, float $amount) : float
    {
        if ($amount <= 0) throw new AppException('1Amount to add should be greater than zero.', 422);

        if($isDebtAccount && $isDebtJournal || !$isDebtAccount && !$isDebtJournal) {
            // add
            // D -> D || K -> K (+)
            return $amount;
        }else if($isDebtAccount && !$isDebtJournal || !$isDebtAccount && $isDebtJournal) {
            // min
            // D -> C || C -> D (-)
            return -$amount;
        }else{
            throw new AppException('Calculation balance account invalid. .', 422);
        }
    }


    /**
     * @throws AppException
     */
    public static function reCalculate(string $startDate, array $categories = [])
    {
        try {
            DB::beginTransaction();
            $today = Carbon::now()->format('Y-m-d');
            $dateBalance = Carbon::parse($startDate)->subDay()->format('Y-m-d');

            $journals = Journal::leftJoin('category_accounts', 'journals.category_account_id', '=', 'category_accounts.id')
                ->leftJoin('code_accounts as da', 'category_accounts.deposit_account_id', '=', 'da.id')
                ->leftJoin('code_accounts as ca', 'category_accounts.code_account_id', '=', 'ca.id')
                ->when(!empty($categories), function ($query) use ($categories) {
                    $query->whereIn('journals.category_account_id', $categories);
                })
                ->where('journals.is_balance', true)
                ->whereDate('journals.date', '>=', $startDate)
                ->whereDate('journals.date', '<=', $today)
                ->select([
                    'journals.*',
                    'ca.id as ca_id',
                    'ca.is_debt as ca_is_debt',
                    'da.id as da_id',
                    'da.is_debt as da_is_debt',
                ])
                ->select([
                    'journals.*',
                    DB::raw('ca.id as ca_id'),
                    DB::raw('ca.is_debt as ca_is_debt'),
                    DB::raw('IFNULL(da.id, NULL) as da_id'),
                    DB::raw('IFNULL(da.is_debt, false) as da_is_debt'),
                ])
                ->orderByRaw('date ASC, number, is_debt DESC')
                ->get();

            $balances = self::getAccountBalances($dateBalance);
            $currentBalance = [];
            foreach ($balances as $balance) {
                $currentBalance[$balance->id] = (float)$balance->balance;
            }

            $i = 0;
            foreach ($journals as $journal) {
                $accountIsDebt = $journal->is_deposit ? $journal->da_is_debt :  $journal->ca_is_debt;
                $accountId = $journal->is_deposit ? $journal->da_id : $journal->ca_id;
                if($accountId == null || $accountIsDebt === null) throw new AppException("Can not find $journal->id deposit/code account.", 422);

                $currentBalance[$accountId] += self::getBalanceOperation(
                    $journal->is_debt,
                    $accountIsDebt,
                    $journal->nominal
                );

                Journal::where('id', $journal->id)->update([
                    'balance' => $currentBalance[$accountId],
                ]);
                $i++;
            }

            foreach ($currentBalance as $id => $balance) {
                CodeAccount::findOrFail($id)->update([
                    'balance' => $balance,
                ]);
            }

            $response = AppResponse::success(null, "Success recalculation $i transactions journals.");
            DB::commit();
            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new AppException($e->getMessage(), 422);
        }
    }



    public static function withMicro(Carbon|string|null $date = null): string
    {
        $now = Carbon::now();
        if (is_null($date)) {
            return $now->format('Y-m-d H:i:s.u');
        }
        $baseDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $newDate = Carbon::createFromFormat(
            'Y-m-d H:i:s.u',
            $baseDate->format('Y-m-d') . ' ' . $now->format('H:i:s.u')
        );

        return $newDate->format('Y-m-d H:i:s.u');
    }

    /**
     * Convert backed enum cases to imploded string.
     *
     * Usage: AppHelper::enumCasesToString(PackageType::class)
     * Result: "direct,closing,free_owner,free_marketing,leader_owner,leader_marketing"
     *
     * @param string $enumClass Fully qualified enum class name
     * @param string $separator Separator for implode (default: ',')
     * @return string
     */
    public static function enumCasesToString(string $enumClass, string $separator = ','): string
    {
        return implode($separator, array_map(fn($case) => $case->value, $enumClass::cases()));
    }

    /**
     * Convert image file to base64 encoded data URI
     *
     * @param string $path Relative path from public directory
     * @return string Base64 encoded image data URI
     */
    public static function base64Image(string $path): string
    {
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            // Return empty 1x1 transparent gif if image not found
            return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        }

        $imageData = file_get_contents($fullPath);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fullPath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }

    /**
     * Generate QR Code from digital signature
     *
     * @param string $signature Digital signature to encode
     * @return string Base64 encoded QR Code image
     */
    public static function generateQrCode(string $signature): string
    {
        if (empty($signature)) {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        }

        // Use QR Server API (more reliable)
        $qrData = urlencode(substr($signature, 0, 100)); // Limit to 100 chars for QR
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$qrData}";

        try {
            $qrImage = file_get_contents($qrUrl);
            if ($qrImage !== false) {
                return 'data:image/png;base64,' . base64_encode($qrImage);
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        // Return placeholder if failed
        return 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect x="10" y="10" width="20" height="20" fill="#000"/><rect x="70" y="10" width="20" height="20" fill="#000"/><rect x="10" y="70" width="20" height="20" fill="#000"/><rect x="40" y="40" width="20" height="20" fill="#000"/><rect x="15" y="15" width="10" height="10" fill="#fff"/><rect x="75" y="15" width="10" height="10" fill="#fff"/><rect x="15" y="75" width="10" height="10" fill="#fff"/><rect x="45" y="45" width="10" height="10" fill="#fff"/></svg>');
    }

}




