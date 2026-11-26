<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class DashboardService
{
    /** @var array<string, StatsProvider> */
    private array $providers = [];

    /**
     * @param  iterable<StatsProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->name()] = $provider;
        }
    }

    /**
     * @return array{data: array<string, array<string, mixed>|null>|null, warnings: list<string>}
     */
    public function forBlock(string $block): array
    {
        $names = $this->namesFor($block);
        $data = [];
        $warnings = [];

        foreach ($names as $name) {
            $stats = $this->statsFor($name);
            $data[$name] = $stats;

            if ($stats === null) {
                $warnings[] = $name;
            }
        }

        if (count($warnings) === count($names)) {
            return ['data' => null, 'warnings' => $warnings];
        }

        return ['data' => $data, 'warnings' => $warnings];
    }

    public function refresh(): void
    {
        foreach (array_keys($this->providers) as $name) {
            Cache::forget($this->cacheKey($name));
        }
    }

    /**
     * @return list<string>
     */
    private function namesFor(string $block): array
    {
        $blocks = (array) config('shop.dashboard.blocks');

        if ($block === (string) config('shop.dashboard.summary_block')) {
            $names = [];

            foreach ($blocks as $members) {
                foreach ((array) $members as $member) {
                    $names[] = (string) $member;
                }
            }

            return array_values(array_unique($names));
        }

        if (! array_key_exists($block, $blocks)) {
            throw new InvalidArgumentException("Unknown dashboard block [{$block}].");
        }

        return array_map('strval', array_values((array) $blocks[$block]));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function statsFor(string $name): ?array
    {
        $provider = $this->providers[$name] ?? null;

        if ($provider === null) {
            Log::warning('Dashboard stats provider is not registered.', ['provider' => $name]);

            return null;
        }

        $ttl = (int) config('shop.stats_cache_ttl');
        $key = $this->cacheKey($name);

        if ($ttl > 0) {
            $cached = Cache::get($key);

            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $stats = $provider->stats();
        } catch (Throwable $e) {
            Log::warning('Dashboard stats provider failed.', ['provider' => $name, 'exception' => $e::class]);

            return null;
        }

        if ($ttl > 0) {
            Cache::put($key, $stats, $ttl);
        }

        return $stats;
    }

    private function cacheKey(string $name): string
    {
        return (string) config('shop.dashboard.cache_prefix').$name;
    }
}
