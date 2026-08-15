<?php

namespace App\Domain\Validation;

final class ValidationService implements ValidationContract
{
    public function domainKey(): string
    {
        return 'validation';
    }
}
