<?php

declare(strict_types=1);

namespace App\Support\Contracts;

interface StatsProvider
{
    /**
     * Container tag every module uses to register its provider.
     */
    public const TAG = 'module.stats';

    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function stats(): array;
}
