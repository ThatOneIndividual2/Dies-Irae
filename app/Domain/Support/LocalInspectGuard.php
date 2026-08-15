<?php

namespace App\Domain\Support;

use RuntimeException;

final class LocalInspectGuard
{
    public static function assertMutable(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Apocalypse debug mutations are local/testing only.');
        }
    }

    public static function allowed(): bool
    {
        return app()->environment(['local', 'testing']);
    }
}
