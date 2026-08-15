<?php

namespace App\Domain\Support;

use Illuminate\Support\Facades\DB;

final class AfterCommit
{
    public static function dispatch(callable $callback): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);

            return;
        }

        $callback();
    }
}
