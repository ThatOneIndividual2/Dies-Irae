<?php

namespace App\Domain\Warfare\State;

final class Casualties
{
    public function __construct(
        public int $killed,
        public int $wounded,
        public int $fled,
        public int $captured,
        public int $banished,
        public int $possessed,
        public int $converted,
    ) {
    }

    public function totalRemoved(): int
    {
        return $this->killed + $this->wounded + $this->fled + $this->captured + $this->banished + $this->possessed + $this->converted;
    }

    public static function none(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0);
    }

    public function mundaneOnly(): bool
    {
        return $this->banished === 0 && $this->possessed === 0 && $this->converted === 0;
    }
}
