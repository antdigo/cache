<?php

namespace Amp\Cache\Test;

use Amp\Cache\Cache;
use Amp\Cache\ProxyCache;
use Amp\Delayed;
use Amp\Loop;
use Amp\Success;

class ProxyCacheTest extends CacheTest
{
    /** @return ProxyCache */
    protected function createCache(): Cache
    {
        return new ProxyCache(
            function ($key) {
                return new Delayed(100, $key);
            },
            3
        );
    }

    public function testGet()
    {
        Loop::run(
            function () {
                $cache = $this->createCache();

                $result = yield $cache->get("mykey");
                $this->assertSame("mykey", $result);

                yield $cache->set("mykey", "myvalue", 10);

                $result = yield $cache->get("mykey");
                $this->assertSame("myvalue", $result);
            }
        );
    }

    public function testEntryIsntReturnedAfterTTLHasPassed()
    {
        Loop::run(
            function () {
                $cache = $this->createCache();

                yield $cache->set("foo", "bar", 0);
                sleep(1);

                $this->assertSame("foo", yield $cache->get("foo"));
            }
        );
    }

    public function testEntryIsntReturnedAfterDelete()
    {
        Loop::run(
            function () {
                $cache = $this->createCache();

                yield $cache->set("foo", "bar");
                yield $cache->delete("foo");

                $this->assertSame("foo", yield $cache->get("foo"));
            }
        );
    }

    /**
     * @expectedException \TypeError
     */
    public function testSetFailsOnInvalidCallbackReturn()
    {
        $cache = new ProxyCache(
            function ($key) {
                return $key;
            }
        );

        $cache->get("foo");
    }

    public function testRaceCondition()
    {
        $cache = $this->createCache();
        $this->assertSame($cache->get("foo"), $cache->get("foo"));
    }
}
