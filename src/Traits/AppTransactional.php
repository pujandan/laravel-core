<?php

namespace Daniardev\LaravelTsd\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use LogicException;

/**
 * Transactional Trait
 *
 * Trait untuk memastikan method dijalankan dalam DB transaction.
 * Gunakan di service method yang melakukan write operations.
 *
 * @package Daniardev\LaravelTsd\Traits
 */
trait AppTransactional
{
    /**
     * Require database transaction
     *
     * Method ini CHECK transaction level Without Exec query ke database.
     * only read property $transactionLevel dari connection object.
     *
     * @throws LogicException if not DB::transaction
     * @return void
     *
     * @example
     * // Di service method yang need transaction
     * public function create(array $data): Model
     * {
     *     $this->requireTransaction();
     *
     *     $model = Model::create($data);
     *     return $model;
     * }
     */
    protected function requireTransaction(): void
    {
        $transactionLevel = DB::transactionLevel();

        if ($transactionLevel === 0) {
            throw new LogicException(
                __('tsd_message.mustUseTransaction'),
                500
            );
        }
    }
}
