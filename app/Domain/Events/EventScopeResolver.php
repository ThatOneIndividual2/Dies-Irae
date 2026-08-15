<?php

namespace App\Domain\Events;

final class EventScopeResolver
{
    /**
     * @param  array<string, mixed>  $definition
     * @return list<array{type: string, id: int}>
     */
    public function candidates(array $definition, EventWorldView $view): array
    {
        $scope = (string) ($definition['scope'] ?? 'world');
        $out = [];
        foreach ($this->ids($scope, $view) as $id) {
            $out[] = ['type' => $scope, 'id' => $id];
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    private function ids(string $scope, EventWorldView $view): array
    {
        return match ($scope) {
            'world', 'global', 'apocalypse' => [$view->worldId],
            'character' => array_map('intval', array_keys($view->characters)),
            'dynasty' => array_map('intval', array_keys($view->dynasties)),
            'settlement' => array_map('intval', array_keys($view->settlements)),
            'territory' => array_map('intval', array_keys($view->territories)),
            'realm' => array_map('intval', array_keys($view->realms)),
            'church_jurisdiction' => array_map('intval', array_keys($view->sees)),
            'monastery' => array_map('intval', array_keys($view->monasteries)),
            'army' => array_map('intval', array_keys($view->armies)),
            'war' => array_map('intval', array_keys($view->wars)),
            'cult' => array_map('intval', array_keys($view->cults)),
            'plague' => array_map('intval', array_keys($view->plagues)),
            default => [],
        };
    }
}
