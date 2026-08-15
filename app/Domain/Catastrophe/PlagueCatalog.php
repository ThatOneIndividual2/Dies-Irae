<?php

namespace App\Domain\Catastrophe;

final class PlagueCatalog
{
    /**
     * @return array<string, PlagueProfile>
     */
    public static function all(): array
    {
        $black = PlagueProfile::blackDeath();
        $pneumonic = PlagueProfile::pneumonic();
        $infernal = PlagueProfile::infernalMiasma();

        return [
            $black->key => $black,
            $pneumonic->key => $pneumonic,
            $infernal->key => $infernal,
        ];
    }

    public static function get(string $key): PlagueProfile
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new \InvalidArgumentException("Unknown plague strain: {$key}");
        }

        return $all[$key];
    }
}
