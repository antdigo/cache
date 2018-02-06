<?php
declare(strict_types=1);

namespace Amp\Cache;

use Amp\Promise;

final class PrefixCache implements Cache
{
    private $cache;

    private $keyPrefix;

    public function __construct(Cache $cache, string $keyPrefix)
    {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Gets the specified key prefix.
     *
     * @return string
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /** @inheritdoc */
    public function get($key): Promise
    {
        if (!\is_string($key)) {
            throw new \TypeError("Cache key must be string");
        }

        return $this->cache->get($this->keyPrefix.$key);
    }

    /** @inheritdoc */
    public function set($key, $value, int $ttl = null): Promise
    {
        if (!\is_string($key)) {
            throw new \TypeError("Cache key must be string");
        }

        return $this->cache->set($this->keyPrefix.$key, $value, $ttl);
    }

    /** @inheritdoc */
    public function delete($key): Promise
    {
        if (!\is_string($key)) {
            throw new \TypeError("Cache key must be string");
        }

        return $this->cache->delete($this->keyPrefix.$key);
    }
}
