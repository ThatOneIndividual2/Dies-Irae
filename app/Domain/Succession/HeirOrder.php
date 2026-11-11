<?php

namespace App\Domain\Succession;

use App\Models\Character;
use Illuminate\Support\Collection;

/**
 * Male-preference primogeniture over legitimate children.
 * Sons by birth, then daughters by birth. No siblings and no claims.
 */
final class HeirOrder
{
    public const LAW_KEY = 'male_preference_primogeniture';

    /**
     * @return Collection<int, Character>
     */
    public function candidates(Character $deceased): Collection
    {
        $children = Character::query()
            ->where('world_id', $deceased->world_id)
            ->where('father_id', $deceased->id)
            ->where('is_alive', true)
            ->where('id', '!=', $deceased->id)
            ->get()
            ->filter(function (Character $child) {
                $status = $child->legitimacy_status ?: 'legitimate';

                return $status === 'legitimate';
            });

        $sons = $this->byBirth($children->filter(fn (Character $child) => $child->sex === 'male'));
        $daughters = $this->byBirth($children->filter(fn (Character $child) => $child->sex !== 'male'));

        return $sons->concat($daughters)->values();
    }

    /**
     * @param  Collection<int, Character>  $people
     * @return Collection<int, Character>
     */
    private function byBirth(Collection $people): Collection
    {
        return $people->sortBy(function (Character $child) {
            return $child->birth_date ? $child->birth_date->toDateString() : '9999-12-31';
        })->values();
    }
}
