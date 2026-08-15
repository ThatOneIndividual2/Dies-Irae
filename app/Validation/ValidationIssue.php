<?php

namespace App\Validation;

class ValidationIssue
{
    public string $code;
    public string $message;
    public string $severity;
    public ?string $entityType;
    public ?int $entityId;
    public array $context;

    public function __construct(
        string $code,
        string $message,
        string $severity = 'error',
        ?string $entityType = null,
        ?int $entityId = null,
        array $context = []
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->severity = $severity;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->context = $context;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'context' => $this->context,
        ];
    }
}
