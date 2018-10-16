<?php

namespace Factories;

use Cache\Adapter\Redis\RedisCachePool;
use Psr\Cache\CacheItemPoolInterface;

class CacheFactory
{
    public function __invoke(): CacheItemPoolInterface
    {
        $client = new \Redis();
        $client->connect(
            getenv('REDIS_HOST'),
            getenv('REDIS_PORT')
        );

        return new RedisCachePool($client);
    }
}