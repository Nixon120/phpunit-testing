<?php

namespace Services;

use MongoDB\Client;

class MongoFactory
{
    /**
     * @return \MongoDB\Database
     */
    public static function getDb()
    {
        $mongoDb = new Client(getenv('MONGO_HOST'));
        return $mongoDb->{getenv('MONGO_DB')};
    }
}
