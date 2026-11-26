<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Contracts\StatsProvider;
use Throwable;

final class FakeStatsProvider implements StatsProvider
{
    public int $calls = 0;

    /**
     * @param  array<string, mixed>|Throwable  $result
     */
    public function __construct(private string $name, private array|Throwable $result) {}

    /**
     * @param  array<string, mixed>|Throwable  $result
     */
    public function setResult(array|Throwable $result): void
    {
        $this->result = $result;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function stats(): array
    {
        $this->calls++;

        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
