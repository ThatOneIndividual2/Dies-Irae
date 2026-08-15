<?php

namespace App\Actions\Titles;

use App\Models\Character;
use App\Models\Title;
use App\Models\TitleOwnership;
use Carbon\CarbonInterface;

final class RevokeTitle
{
    public function __construct(private TitleOwnershipMutator $mutator)
    {
    }

    public function execute(Title $title, CarbonInterface $date, ?Character $revokedBy = null): ?TitleOwnership
    {
        return $this->mutator->revoke($title, $date, $revokedBy);
    }
}
