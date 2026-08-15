<?php

namespace App\Domain\Events;

final class EventDefinitionValidator
{
    /**
     * @return list<string>
     */
    public function validateCatalog(EventCatalog $catalog): array
    {
        $errors = [];
        $scopes = $catalog->scopes();
        $categories = $catalog->categories();
        $visibilities = $catalog->visibilities();
        $ops = $catalog->ops();

        if ($scopes === []) {
            $errors[] = 'catalog.scopes missing';
        }
        if ($categories === []) {
            $errors[] = 'catalog.categories missing';
        }

        $keys = [];
        foreach ($catalog->definitions() as $definition) {
            $key = (string) ($definition['key'] ?? '');
            if ($key === '') {
                $errors[] = 'event missing key';
                continue;
            }
            if (isset($keys[$key])) {
                $errors[] = "duplicate event key {$key}";
            }
            $keys[$key] = true;
            $errors = array_merge($errors, $this->validateDefinition($definition, $scopes, $categories, $visibilities, $ops));
        }

        foreach ($catalog->definitions() as $definition) {
            foreach ($definition['choices'] ?? [] as $choice) {
                foreach (array_merge($choice['immediate'] ?? [], $choice['hidden'] ?? [], $choice['delayed'] ?? []) as $op) {
                    if (! is_array($op)) {
                        continue;
                    }
                    if (($op['op'] ?? '') === 'spawn_event') {
                        $target = (string) ($op['event'] ?? '');
                        if ($target !== '' && ! $catalog->has($target)) {
                            $errors[] = $definition['key'].' spawns unknown event '.$target;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  list<string>  $scopes
     * @param  list<string>  $categories
     * @param  list<string>  $visibilities
     * @param  list<string>  $ops
     * @return list<string>
     */
    private function validateDefinition(
        array $definition,
        array $scopes,
        array $categories,
        array $visibilities,
        array $ops
    ): array {
        $errors = [];
        $key = $definition['key'];
        $scope = (string) ($definition['scope'] ?? '');
        if (! in_array($scope, $scopes, true) && $scope !== 'global') {
            $errors[] = "{$key}: unknown scope {$scope}";
        }
        $category = (string) ($definition['category'] ?? '');
        if (! in_array($category, $categories, true)) {
            $errors[] = "{$key}: unknown category {$category}";
        }
        $visibility = (string) ($definition['visibility'] ?? 'player');
        if (! in_array($visibility, $visibilities, true)) {
            $errors[] = "{$key}: unknown visibility {$visibility}";
        }
        if (! isset($definition['weight']) || (int) $definition['weight'] < 0) {
            $errors[] = "{$key}: weight must be >= 0";
        }
        if (! isset($definition['trigger']) || ! is_array($definition['trigger'])) {
            $errors[] = "{$key}: trigger object required";
        }
        $choices = $definition['choices'] ?? null;
        if (! is_array($choices) || $choices === []) {
            $errors[] = "{$key}: at least one choice required";
        } else {
            $choiceKeys = [];
            foreach ($choices as $choice) {
                if (! is_array($choice) || empty($choice['key'])) {
                    $errors[] = "{$key}: choice missing key";
                    continue;
                }
                if (isset($choiceKeys[$choice['key']])) {
                    $errors[] = "{$key}: duplicate choice {$choice['key']}";
                }
                $choiceKeys[$choice['key']] = true;
                foreach (['immediate', 'hidden'] as $bucket) {
                    foreach ($choice[$bucket] ?? [] as $op) {
                        $errors = array_merge($errors, $this->validateOp($key, $op, $ops));
                    }
                }
                foreach ($choice['delayed'] ?? [] as $op) {
                    $errors = array_merge($errors, $this->validateOp($key, $op, $ops));
                    if (is_array($op) && ! isset($op['days']) && ! isset($op['event'])) {
                        $errors[] = "{$key}: delayed op needs days";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param  mixed  $op
     * @param  list<string>  $ops
     * @return list<string>
     */
    private function validateOp(string $key, $op, array $ops): array
    {
        if (! is_array($op) || empty($op['op'])) {
            return ["{$key}: op missing name"];
        }
        $name = (string) $op['op'];
        if (! in_array($name, $ops, true)) {
            return ["{$key}: unknown op {$name}"];
        }

        return [];
    }
}
