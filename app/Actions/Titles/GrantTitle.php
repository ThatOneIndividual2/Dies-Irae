<?php

namespace App\Actions\Titles;

use App\Domain\Enums\AcquisitionType;
use App\Models\Character;
use App\Models\Title;
use App\Models\TitleOwnership;
use Carbon\CarbonInterface;

final class GrantTitle
{
    public function __construct(private TitleOwnershipMutator $mutator)
    {
    }

    public function execute(
        Title $title,
        Character $holder,
        CarbonInterface $date,
        ?Character $grantedBy = null,
        string $acquisitionType = AcquisitionType::GRANT
    ): TitleOwnership {
        return $this->mutator->grant($title, $holder, $acquisitionType, $date, $grantedBy);
    }
}
