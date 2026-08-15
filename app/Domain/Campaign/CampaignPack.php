<?php

namespace App\Domain\Campaign;

final class CampaignPack
{
    private array $files = [];

    public static function europa1347(): self
    {
        $relative = config('campaign.1347.data_path', 'database/data/campaign/1347');
        $path = str_starts_with($relative, '/') ? $relative : base_path($relative);

        return new self($path);
    }

    public function __construct(private string $directory)
    {
    }

    public function get(string $name): array
    {
        if (!isset($this->files[$name])) {
            $path = rtrim($this->directory, '/').'/'.$name.'.json';
            $this->files[$name] = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->files[$name];
    }
}
