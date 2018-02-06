<?php
declare(strict_types=1);

namespace Amp\Cache;

use Amp\Promise;
use Amp\Success;

/**
 * Cache implementation that just ignores all operations and always resolves to `null`.
 */
class NullCache implements Cache
{
    /** @inheritdoc */
    public function get($key): Promise
    {
        return new Success;
    }

    /** @inheritdoc */
    public function set($key, $value, int $ttl = null): Promise
    {
        return new Success;
    }

    /** @inheritdoc */
    public function delete($key): Promise
    {
        return new Success(false);
    }
}
