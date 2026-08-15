<?php

namespace App\Domain\Support;

use Illuminate\Support\Facades\DB;

final class Transactional
{
    public static function run(callable $callback)
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
