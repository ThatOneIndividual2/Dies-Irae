<?php

namespace App\Validation;

class ValidationReport
{
    public int $worldId;

    /** @var list<ValidationIssue> */
    private array $issues = [];

    public function __construct(int $worldId)
    {
        $this->worldId = $worldId;
    }

    public function add(ValidationIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    /** @return list<ValidationIssue> */
    public function issues(): array
    {
        return $this->issues;
    }

    /** @return list<ValidationIssue> */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            fn (ValidationIssue $i) => $i->severity === 'error'
        ));
    }

    public function passed(): bool
    {
        return count($this->errors()) === 0;
    }

    public function hasCode(string $code): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->code === $code) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'world_id' => $this->worldId,
            'passed' => $this->passed(),
            'error_count' => count($this->errors()),
            'issue_count' => count($this->issues),
            'issues' => array_map(fn (ValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }
}
