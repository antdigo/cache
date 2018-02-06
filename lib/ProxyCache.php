<?php
declare(strict_types=1);

namespace Amp\Cache;

use Amp\Deferred;
use Amp\Promise;
use Amp\Success;

class ProxyCache implements Cache
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var bool
     */
    private $useStale;

    /**
     * @var \Amp\Promise[]
     */
    private $queue;

    /**
     * @var array
     */
    private $cache;

    /**
     * @var array
     */
    private $cacheTimeouts;

    /**
     * @param callable $callback
     * @param int|null $ttl
     * @param bool     $useStale
     */
    public function __construct(callable $callback, int $ttl = null, bool $useStale = false)
    {
        $this->callback = $callback;
        $this->ttl = $ttl;
        $this->useStale = $useStale;
        $this->queue = [];
        $this->cache = [];
        $this->cacheTimeouts = [];
    }

    public function __destruct()
    {
        $this->callback = null;
        $this->ttl = null;
        $this->useStale = null;
        $this->queue = null;
        $this->cache = null;
        $this->cacheTimeouts = null;
    }

    /** @inheritdoc */
    public function get($key): Promise
    {
        if (!isset($this->cache[$key])) {
            $result = $this->cache[$key] = $this->defer($key);
        } elseif (isset($this->cacheTimeouts[$key]) && $this->cacheTimeouts[$key] < \time()) {
            $promise = $this->defer($key);
            if ($this->useStale === false) {
                unset(
                    $this->cache[$key],
                    $this->cacheTimeouts[$key]
                );
                $this->cache[$key] = $promise;
            }
            $result = $this->cache[$key];
        } else {
            $result = $this->cache[$key];
        }

        return $result;
    }

    /** @inheritdoc */
    public function set($key, $value, int $ttl = null): Promise
    {
        $expiry = \time();
        if ($ttl === null) {
            $expiry += $this->ttl;
        } elseif ($ttl >= 0) {
            $expiry += $ttl;
        } else {
            throw new \Error("Invalid cache TTL ({$ttl}; integer >= 0 or null required");
        }

        $this->cacheTimeouts[$key] = $expiry;
        unset($this->cache[$key]);
        $this->cache[$key] = new Success($value);

        return new Success;
    }

    /** @inheritdoc */
    public function delete($key): Promise
    {
        $exists = isset($this->cache[$key]);

        unset(
            $this->cache[$key],
            $this->cacheTimeouts[$key]
        );

        return new Success($exists);
    }

    /**
     * @param $key
     * @return \Amp\Promise
     * @throws \TypeError
     */
    protected function defer($key): Promise
    {
        if (isset($this->queue[$key])) {
            return $this->queue[$key];
        }

        $defer = new Deferred();
        $promise = $this->queue[$key] = $defer->promise();
        $value = call_user_func($this->callback, $key);

        if ($value instanceof Promise === false) {
            throw new \TypeError('Callback must return \Amp\Promise');
        }

        $value->onResolve(
            function ($error, $value) use ($defer, $key) {
                if (isset($error)) {
                    $defer->fail($error);
                } else {
                    $defer->resolve($value);
                    $this->cache[$key] = $defer->promise();
                    $this->cacheTimeouts[$key] = $this->ttl + \time();
                }
                unset($this->queue[$key]);
            }
        );

        return $promise;
    }
}
