<?php

namespace App\Domain\World;

final class HistoricalPack
{
    public const SLUG = 'europa-1347';

    public static function directory(): string
    {
        return database_path('data/europa/1347');
    }

    public static function load(): array
    {
        $dir = self::directory();
        $read = static function (string $file) use ($dir): array {
            $path = $dir.'/'.$file;
            if (! is_file($path)) {
                throw new \RuntimeException("Missing historical pack file: {$file}");
            }

            return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        };

        return [
            'world' => $read('world.json'),
            'geography' => $read('geography.json'),
            'dynasties' => $read('dynasties.json'),
            'characters' => $read('characters.json'),
            'titles' => $read('titles.json'),
            'realms' => $read('realms.json'),
            'vassals' => $read('vassals.json'),
            'church' => $read('church.json'),
            'sacred' => $read('sacred_sites.json'),
            'trade' => $read('trade.json'),
            'relations' => $read('relations.json'),
            'plague' => $read('plague.json'),
        ];
    }
}
