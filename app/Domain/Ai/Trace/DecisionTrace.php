<?php

namespace App\Domain\Ai\Trace;

final class DecisionTrace
{
    public string $actorId;
    public string $actorType;
    public string $reasoner;
    /** @var list<ScoredAction> */
    public array $considered = [];
    /** @var list<RejectedAction> */
    public array $rejected = [];
    public ?ScoredAction $chosen = null;
    /** @var list<string> */
    public array $majorModifiers = [];

    public function toArray(): array
    {
        return [
            'actorId' => $this->actorId,
            'actorType' => $this->actorType,
            'reasoner' => $this->reasoner,
            'considered' => array_map(fn (ScoredAction $s) => $s->toArray(), $this->considered),
            'rejected' => array_map(fn (RejectedAction $r) => $r->toArray(), $this->rejected),
            'chosen' => $this->chosen?->toArray(),
            'majorModifiers' => $this->majorModifiers,
        ];
    }

    public function explain(): string
    {
        $lines = [
            "AI {$this->actorType} {$this->actorId} via {$this->reasoner}",
        ];
        foreach ($this->rejected as $r) {
            $lines[] = "  rejected {$r->key}: {$r->reason}";
        }
        foreach ($this->considered as $s) {
            $mods = $s->modifiers === [] ? '' : ' ['.implode('; ', $s->modifiers).']';
            $mark = ($this->chosen && $this->chosen->key === $s->key) ? '*' : ' ';
            $lines[] = sprintf('%s considered %s raw=%.3f final=%.3f%s', $mark, $s->key, $s->raw, $s->final, $mods);
        }
        if ($this->majorModifiers !== []) {
            $lines[] = '  major: '.implode('; ', $this->majorModifiers);
        }
        $lines[] = '  chosen: '.($this->chosen->key ?? 'none');

        return implode("\n", $lines);
    }
}
