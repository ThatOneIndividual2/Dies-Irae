<?php

namespace App\Domain\Campaign\Fog;

use App\Domain\Campaign\CampaignPack;
use App\Models\ApocalypseState;
use App\Models\CampaignEvidence;
use App\Models\CampaignState;
use App\Models\World;
use Illuminate\Support\Collection;

final class ApocalypseFog
{
    public function __construct(private ?CampaignPack $pack = null)
    {
    }

    public function isCampaignWorld(World $world): bool
    {
        return CampaignState::query()->where('world_id', $world->id)->exists();
    }

    /**
     * @return array{headline: string, body: string, band: string, evidences: Collection, admin: array|null}
     */
    public function playerView(World $world, bool $admin = false): array
    {
        $state = $world->apocalypseState;
        $band = $this->band($state);
        $evidences = CampaignEvidence::query()
            ->where('world_id', $world->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $view = [
            'headline' => $band['headline'],
            'body' => $band['body'],
            'band' => $band['key'],
            'evidences' => $evidences,
            'admin' => null,
        ];

        if ($admin) {
            $view['admin'] = [
                'phase_key' => $state?->phase_key,
                'stage' => $state?->stage,
                'pressure' => $state?->pressure,
                'plague_severity' => $state?->plague_severity,
                'demonic_manifestation' => $state?->demonic_manifestation,
                'global_corruption' => $state?->global_corruption,
                'church_cohesion' => $state?->church_cohesion,
                'despair' => $state?->despair,
            ];
        }

        return $view;
    }

    public function dashboardLine(World $world): string
    {
        if (!$this->isCampaignWorld($world)) {
            $state = $world->apocalypseState;

            return (string) ($state->phase_key ?? $state->stage ?? 'unknown');
        }

        return $this->band($world->apocalypseState)['headline'];
    }

    private function band(?ApocalypseState $state): array
    {
        $fog = ($this->pack ?? CampaignPack::europa1347())->get('fog');
        $pressure = (int) ($state->pressure ?? 0);
        $plague = (int) ($state->plague_severity ?? 0);
        $manifest = (int) ($state->demonic_manifestation ?? 0);
        $chosen = $fog['bands'][0];
        foreach ($fog['bands'] as $band) {
            if ($pressure <= (int) $band['max_pressure'] && $plague <= (int) $band['max_plague'] && $manifest <= (int) $band['max_manifestation']) {
                $chosen = $band;
                break;
            }
        }

        return $chosen;
    }
}
