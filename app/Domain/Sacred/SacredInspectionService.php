<?php

namespace App\Domain\Sacred;

use App\Domain\Sacred\Policies\CanonizationPolicy;
use App\Domain\Sacred\Policies\RelicVisibilityPolicy;
use App\Models\Miracle;
use App\Models\PilgrimageRoute;
use App\Models\PilgrimageTraffic;
use App\Models\Relic;
use App\Models\Saint;

final class SacredInspectionService
{
    public function __construct(
        private RelicVisibilityPolicy $relics,
        private CanonizationPolicy $canonization,
        private MiracleService $miracles
    ) {
    }

    public function inspectSaint(Saint $saint): array
    {
        return [
            'saint' => $saint->toArray(),
            'evidence_weight' => $this->canonization->evidenceWeight($saint),
            'evidence_types' => $this->canonization->distinctTypes($saint),
            'cult_intensity' => $this->canonization->cultIntensity($saint),
            'ready_for_canonization' => $saint->character_id
                ? $this->canonization->hasSufficientEvidence($saint)
                : true,
            'evidences' => $saint->evidences()->get()->toArray(),
            'patronages' => $saint->patronages()->get()->toArray(),
            'feasts' => $saint->feasts()->get()->toArray(),
            'shrines' => $saint->shrines()->get()->toArray(),
            'cults' => $saint->cults()->get()->toArray(),
        ];
    }

    public function inspectRelic(Relic $relic): array
    {
        $relic->makeVisible(['true_nature']);

        return [
            'public' => $this->relics->publicView($relic),
            'admin' => $this->relics->adminView($relic),
            'custody' => optional($relic->currentCustody)->toArray(),
            'provenances' => $relic->provenances()->get()->toArray(),
            'events' => $relic->relicEvents()->orderByDesc('id')->limit(50)->get()->toArray(),
        ];
    }

    public function inspectRoute(PilgrimageRoute $route): array
    {
        return [
            'route' => $route->toArray(),
            'stops' => $route->stops()->get()->toArray(),
            'traffic' => PilgrimageTraffic::query()->where('pilgrimage_route_id', $route->id)->orderByDesc('id')->limit(20)->get()->toArray(),
        ];
    }

    public function inspectMiracle(Miracle $miracle): array
    {
        return [
            'miracle' => $miracle->toArray(),
            'causation' => $miracle->causation,
            'interpretations' => $this->miracles->competingReadings($miracle),
            'witnesses' => $miracle->witnesses()->get()->toArray(),
        ];
    }
}
