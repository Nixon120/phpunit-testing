<?php

namespace Services;

use Cache\Adapter\Common\Exception\CacheException;
use DateTime;
use Psr\Cache\CacheItemPoolInterface;
use Cache\Adapter\Common\CacheItem;

class CacheService
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheFactory;

    public function __construct(CacheItemPoolInterface $cacheFactory)
    {
        $this->cacheFactory = $cacheFactory;
    }

    public function getCachedItem(string $key)
    {
        return $this
            ->getCache()
            ->getItem($key)
            ->get();
    }

    public function cachedItemExists(string $key): bool
    {
        return $this
            ->getCache()
            ->hasItem($key);
    }

    public function cacheItem($item, string $key)
    {
        $cachedItem = new CacheItem($key);
        $cachedItem->set($item);
        if (strpos($key, 'LOCKOUT_') !== false) {
            $cachedItem->expiresAt(new DateTime('+15 minutes'));
        }
        $this->getCache()->save($cachedItem);
    }

    public function getCache(): CacheItemPoolInterface
    {
        return $this->cacheFactory;
    }

    public function clearItem($key)
    {
        try {
            return $this->getCache()->deleteItem($key);
        } catch (CacheException $exception) {
            return false;
        }
    }
}
