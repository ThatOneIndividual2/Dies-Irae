<?php

namespace App\Domain\Events;

final class EventConditionEvaluator
{
    /**
     * @param  array<string, mixed>|null  $when
     */
    public function matches(?array $when, EventWorldView $view, string $scopeType, int $scopeId): bool
    {
        if ($when === null || $when === []) {
            return true;
        }

        if (isset($when['all_of']) && is_array($when['all_of'])) {
            foreach ($when['all_of'] as $clause) {
                if (! is_array($clause) || ! $this->matches($clause, $view, $scopeType, $scopeId)) {
                    return false;
                }
            }
        }

        if (isset($when['any_of']) && is_array($when['any_of'])) {
            $ok = false;
            foreach ($when['any_of'] as $clause) {
                if (is_array($clause) && $this->matches($clause, $view, $scopeType, $scopeId)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return false;
            }
        }

        if (isset($when['not']) && is_array($when['not'])) {
            if ($this->matches($when['not'], $view, $scopeType, $scopeId)) {
                return false;
            }
        }

        if (isset($when['min_apocalypse_phase_ordinal']) && $view->phaseOrdinal < (int) $when['min_apocalypse_phase_ordinal']) {
            return false;
        }
        if (isset($when['max_apocalypse_phase_ordinal']) && $view->phaseOrdinal > (int) $when['max_apocalypse_phase_ordinal']) {
            return false;
        }
        if (isset($when['min_pressure']) && $view->pressure < (int) $when['min_pressure']) {
            return false;
        }

        foreach ($when['min_apocalypse_meters'] ?? [] as $meter => $minimum) {
            if ((int) ($view->meters[$meter] ?? 0) < (int) $minimum) {
                return false;
            }
        }
        foreach ($when['max_apocalypse_meters'] ?? [] as $meter => $maximum) {
            if ((int) ($view->meters[$meter] ?? 0) > (int) $maximum) {
                return false;
            }
        }

        foreach ($when['min_hooks'] ?? [] as $hook => $minimum) {
            if ($view->hookIntensity((string) $hook, $scopeType, $scopeId) < (int) $minimum) {
                return false;
            }
        }
        foreach ($when['max_hooks'] ?? [] as $hook => $maximum) {
            if ($view->hookIntensity((string) $hook, $scopeType, $scopeId) > (int) $maximum) {
                return false;
            }
        }
        if (isset($when['has_hook']) && ! $view->hasHook((string) $when['has_hook'], $scopeType, $scopeId)) {
            return false;
        }
        if (isset($when['missing_hook']) && $view->hasHook((string) $when['missing_hook'], $scopeType, $scopeId)) {
            return false;
        }

        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        foreach ($when['min_territory'] ?? [] as $field => $minimum) {
            if ((int) ($territory[$field] ?? 0) < (int) $minimum) {
                return false;
            }
        }
        foreach ($when['max_territory'] ?? [] as $field => $maximum) {
            if ((int) ($territory[$field] ?? 0) > (int) $maximum) {
                return false;
            }
        }

        $actorId = $this->actorId($view, $scopeType, $scopeId);
        $standing = $actorId ? (int) (($view->characters[$actorId]['church_standing'] ?? 0)) : 0;
        if (isset($when['min_church_standing']) && $standing < (int) $when['min_church_standing']) {
            return false;
        }
        if (isset($when['max_church_standing']) && $standing > (int) $when['max_church_standing']) {
            return false;
        }

        if (array_key_exists('plague_active', $when)) {
            $wanted = (bool) $when['plague_active'];
            $active = $this->plagueActive($view, $scopeType, $scopeId);
            if ($active !== $wanted) {
                return false;
            }
        }

        if (array_key_exists('has_cult', $when)) {
            $wanted = (bool) $when['has_cult'];
            if ($this->hasCult($view, $scopeType, $scopeId) !== $wanted) {
                return false;
            }
        }
        if (isset($when['cult_min_strength'])) {
            if ($this->cultStrength($view, $scopeType, $scopeId) < (int) $when['cult_min_strength']) {
                return false;
            }
        }
        if (array_key_exists('has_monastery', $when)) {
            if ($this->hasMonastery($view, $scopeType, $scopeId) !== (bool) $when['has_monastery']) {
                return false;
            }
        }
        if (array_key_exists('has_war', $when)) {
            $hasWar = $view->wars !== [];
            if ($scopeType === 'war') {
                $hasWar = isset($view->wars[$scopeId]);
            }
            if ($hasWar !== (bool) $when['has_war']) {
                return false;
            }
        }
        if (isset($when['min_army_strength'])) {
            if ($this->armyStrength($view, $scopeType, $scopeId) < (int) $when['min_army_strength']) {
                return false;
            }
        }
        if (isset($when['character_alive']) && $scopeType === 'character') {
            $alive = (bool) ($view->characters[$scopeId]['is_alive'] ?? false);
            if ($alive !== (bool) $when['character_alive']) {
                return false;
            }
        }
        foreach ($when['min_character'] ?? [] as $field => $minimum) {
            $id = $scopeType === 'character' ? $scopeId : $actorId;
            if (! $id || (int) ($view->characters[$id][$field] ?? 0) < (int) $minimum) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveTerritory(EventWorldView $view, string $scopeType, int $scopeId): array
    {
        if ($scopeType === 'territory') {
            return $view->territory($scopeId);
        }
        if ($scopeType === 'settlement') {
            $settlement = $view->settlements[$scopeId] ?? [];
            $tid = (int) ($settlement['territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'monastery') {
            $house = $view->monasteries[$scopeId] ?? [];
            $tid = (int) ($house['territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'cult') {
            $cult = $view->cults[$scopeId] ?? [];
            $tid = (int) ($cult['territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'army') {
            $army = $view->armies[$scopeId] ?? [];
            $tid = (int) ($army['territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'church_jurisdiction') {
            $see = $view->sees[$scopeId] ?? [];
            $tid = (int) ($see['territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'character') {
            $character = $view->characters[$scopeId] ?? [];
            $tid = (int) ($character['residence_territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }
        if ($scopeType === 'plague') {
            $wave = $view->plagues[$scopeId] ?? [];
            $tid = (int) ($wave['origin_territory_id'] ?? 0);

            return $tid ? $view->territory($tid) : [];
        }

        return [];
    }

    public function actorId(EventWorldView $view, string $scopeType, int $scopeId): ?int
    {
        if ($scopeType === 'character') {
            return $scopeId;
        }
        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        $owner = (int) ($territory['owner_character_id'] ?? 0);
        if ($owner) {
            return $owner;
        }
        if ($scopeType === 'realm') {
            return (int) (($view->realms[$scopeId]['top_liege_character_id'] ?? 0) ?: 0) ?: null;
        }
        if ($scopeType === 'dynasty') {
            return (int) (($view->dynasties[$scopeId]['head_character_id'] ?? 0) ?: 0) ?: null;
        }
        if ($scopeType === 'army') {
            $army = $view->armies[$scopeId] ?? [];

            return (int) (($army['owner_character_id'] ?? $army['commander_character_id'] ?? 0) ?: 0) ?: null;
        }
        foreach ($view->characters as $id => $character) {
            if (! empty($character['is_alive'])) {
                return (int) $id;
            }
        }

        return null;
    }

    private function plagueActive(EventWorldView $view, string $scopeType, int $scopeId): bool
    {
        if ($scopeType === 'plague') {
            return (($view->plagues[$scopeId]['status'] ?? '') === 'active');
        }
        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        if ((int) ($territory['plague_intensity'] ?? 0) > 0) {
            return true;
        }
        foreach ($view->plagues as $wave) {
            if (($wave['status'] ?? '') === 'active') {
                return true;
            }
        }

        return false;
    }

    private function hasCult(EventWorldView $view, string $scopeType, int $scopeId): bool
    {
        if ($scopeType === 'cult') {
            return isset($view->cults[$scopeId]) && empty($view->cults[$scopeId]['destroyed']);
        }
        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        $tid = (int) ($territory['id'] ?? 0);
        foreach ($view->cults as $cult) {
            if (! empty($cult['destroyed'])) {
                continue;
            }
            if ($tid && (int) ($cult['territory_id'] ?? 0) === $tid) {
                return true;
            }
            if ($tid === 0) {
                return true;
            }
        }

        return false;
    }

    private function cultStrength(EventWorldView $view, string $scopeType, int $scopeId): int
    {
        if ($scopeType === 'cult') {
            return (int) ($view->cults[$scopeId]['strength'] ?? 0);
        }
        $best = 0;
        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        $tid = (int) ($territory['id'] ?? 0);
        foreach ($view->cults as $cult) {
            if (! empty($cult['destroyed'])) {
                continue;
            }
            if ($tid && (int) ($cult['territory_id'] ?? 0) !== $tid) {
                continue;
            }
            $best = max($best, (int) ($cult['strength'] ?? 0));
        }

        return $best;
    }

    private function hasMonastery(EventWorldView $view, string $scopeType, int $scopeId): bool
    {
        if ($scopeType === 'monastery') {
            return isset($view->monasteries[$scopeId]);
        }
        $territory = $this->resolveTerritory($view, $scopeType, $scopeId);
        $tid = (int) ($territory['id'] ?? 0);
        foreach ($view->monasteries as $house) {
            if ($tid && (int) ($house['territory_id'] ?? 0) === $tid) {
                return true;
            }
        }

        return $view->monasteries !== [];
    }

    private function armyStrength(EventWorldView $view, string $scopeType, int $scopeId): int
    {
        if ($scopeType === 'army') {
            return (int) ($view->armies[$scopeId]['strength'] ?? 0);
        }
        $sum = 0;
        foreach ($view->armies as $army) {
            if (empty($army['is_active'])) {
                continue;
            }
            $sum += (int) ($army['strength'] ?? 0);
        }

        return $sum;
    }
}
