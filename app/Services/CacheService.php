<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Service
 *
 * OOAD: Service for caching abstraction
 * Clean Architecture: Abstracts caching implementation
 */
class CacheService
{
    /**
     * Remember value in cache.
     *
     * @template T
     * @param string $key
     * @param DateTime|\DateTimeInterface|int|null $ttl
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget cached value.
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Flush all cache.
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Get cache key with prefix.
     */
    public function key(string ...$parts): string
    {
        return implode(':', $parts);
    }
}
