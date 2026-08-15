<?php

namespace App\Domain\Spiritual\Policies;

use App\Domain\Enums\DemonicInfluenceStage;
use App\Domain\Spiritual\Ports\ClericalAuthorityPort;
use App\Models\Character;
use App\Models\DemonicInfluence;

final class ClericalInsightPolicy
{
    public function __construct(private ClericalAuthorityPort $clergy)
    {
    }

    public function maySeeCanonicalStanding(Character $observer, Character $subject): bool
    {
        return $this->clergy->isCleric($observer)
            && $this->clergy->hasJurisdiction($observer, $subject);
    }

    public function maySenseDemonicInfluence(Character $observer, ?DemonicInfluence $influence): bool
    {
        if ($influence === null) {
            return false;
        }

        if ($this->clergy->isLicensedExorcist($observer)) {
            return DemonicInfluenceStage::weight($influence->stage) >= DemonicInfluenceStage::weight(DemonicInfluenceStage::OPPRESSION);
        }

        if ($this->clergy->isCleric($observer)) {
            return DemonicInfluenceStage::publiclyInferable($influence->stage);
        }

        return false;
    }
}
