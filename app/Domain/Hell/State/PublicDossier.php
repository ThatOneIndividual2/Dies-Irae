<?php

namespace App\Domain\Hell\State;

/**
 * What a given observer is allowed to know. True state is never copied here.
 */
final class PublicDossier
{
    public string $demonId;
    public string $shownName;
    public bool $trueIdentityKnown = false;
    public ?string $rank = null;
    public ?string $motives = null;
    public ?string $location = null;
    /** @var list<string> */
    public array $vulnerabilities = [];
    /** @var list<string> */
    public array $knownManifestations = [];
    public string $statusHeard = 'unknown';

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
