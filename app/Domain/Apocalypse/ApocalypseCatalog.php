<?php

namespace App\Domain\Apocalypse;

use InvalidArgumentException;

final class ApocalypseCatalog
{
    /** @var array<string, mixed> */
    private array $phaseFile;

    /** @var array<string, array<string, mixed>> */
    private array $signals = [];

    /** @var array<string, array<string, mixed>> */
    private array $milestones = [];

    /**
     * @param  array<string, mixed>  $phaseFile
     * @param  array<string, mixed>  $signalFile
     * @param  array<string, mixed>  $milestoneFile
     */
    public function __construct(array $phaseFile, array $signalFile, array $milestoneFile)
    {
        $this->phaseFile = $phaseFile;

        foreach ($signalFile['signals'] ?? [] as $signal) {
            $this->signals[$signal['key']] = $signal;
        }

        foreach ($milestoneFile['milestones'] ?? [] as $milestone) {
            $this->milestones[$milestone['key']] = $milestone;
        }
    }

    public static function fromDirectory(string $directory): self
    {
        return new self(
            self::readJson($directory.'/phases.json'),
            self::readJson($directory.'/signals.json'),
            self::readJson($directory.'/milestones.json')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("Apocalypse catalog file missing: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Invalid apocalypse catalog JSON: {$path}");
        }

        return $decoded;
    }

    /** @return list<string> */
    public function meterKeys(): array
    {
        return $this->phaseFile['meter_keys'] ?? ApocalypseMeters::KEYS;
    }

    /** @return array<string, int> */
    public function startingMeters(): array
    {
        return $this->phaseFile['starting_meters'] ?? ApocalypseMeters::ordinaryDefaults();
    }

    public function startingPhaseKey(): string
    {
        return (string) ($this->phaseFile['starting_phase'] ?? 'ordinary');
    }

    /** @return list<array<string, mixed>> */
    public function phases(): array
    {
        return $this->phaseFile['phases'] ?? [];
    }

    /** @return array<string, mixed> */
    public function phase(string $key): array
    {
        foreach ($this->phases() as $phase) {
            if ($phase['key'] === $key) {
                return $phase;
            }
        }

        throw new InvalidArgumentException("Unknown apocalypse phase: {$key}");
    }

    public function phaseByOrdinal(int $ordinal): ?array
    {
        foreach ($this->phases() as $phase) {
            if ((int) $phase['ordinal'] === $ordinal) {
                return $phase;
            }
        }

        return null;
    }

    public function nextPhase(string $currentKey): ?array
    {
        $current = $this->phase($currentKey);
        $nextKey = $current['advance_to'] ?? null;

        return $nextKey ? $this->phase($nextKey) : null;
    }

    /** @return array<string, mixed> */
    public function signal(string $key): array
    {
        if (!isset($this->signals[$key])) {
            throw new InvalidArgumentException("Unknown apocalypse signal: {$key}");
        }

        return $this->signals[$key];
    }

    public function hasSignal(string $key): bool
    {
        return isset($this->signals[$key]);
    }

    /** @return array<string, array<string, mixed>> */
    public function signals(): array
    {
        return $this->signals;
    }

    /** @return array<string, array<string, mixed>> */
    public function milestones(): array
    {
        return $this->milestones;
    }

    /** @return array<string, mixed> */
    public function milestone(string $key): array
    {
        if (!isset($this->milestones[$key])) {
            throw new InvalidArgumentException("Unknown apocalypse milestone: {$key}");
        }

        return $this->milestones[$key];
    }
}
