<?php

namespace Amp\Cache\Test;

use Amp\Cache\Cache;
use Amp\Cache\ProxyCache;
use Amp\Delayed;
use Amp\Loop;
use Amp\Success;

class ProxyCacheStaleTest extends ProxyCacheTest
{
    /** @return ProxyCache */
    protected function createCache(): Cache
    {
        return $this->createAsyncCache();
    }

    /** @return ProxyCache */
    protected function createSyncCache(): Cache
    {
        return new ProxyCache(
            function ($key) {
                return new Success($key);
            },
            3,
            true
        );
    }

    /** @return ProxyCache */
    protected function createAsyncCache(): Cache
    {
        return new ProxyCache(
            function ($key) {
                return new Delayed(100, $key);
            },
            3,
            true
        );
    }

    public function testEntryIsntReturnedAfterTTLHasPassed()
    {
        Loop::run(
            function () {
                $asyncCache = $this->createAsyncCache();

                yield $asyncCache->set("foo", "bar", 0);
                sleep(1);

                $this->assertSame("bar", yield $asyncCache->get("foo"));

                $syncCache = $this->createSyncCache();
                yield $syncCache->set("foo", "bar", 0);
                sleep(1);

                $this->assertSame("foo", yield $syncCache->get("foo"));
            }
        );
    }

    public function testStale()
    {
        Loop::run(
            function () {
                $cache = $this->createCache();

                yield $cache->set("foo", "bar", 0);
                Loop::delay(
                    1100,
                    function () use ($cache) {
                        $this->assertSame("bar", yield $cache->get("foo"));
                    }
                );
                yield $cache->get("foo");
                Loop::delay(
                    1500,
                    function () use ($cache) {
                        $this->assertSame("foo", yield $cache->get("foo"));
                    }
                );
            }
        );
    }
}
