<?php

namespace Traits;

use Services\MongoFactory;

trait MongoAwareTrait
{
    /**
     * @var \MongoDB\Database
     */
    private $mongoDb;

    /**
     * @param \MongoDB\Database $mongoDb
     */
    public function setMongo(\MongoDB\Database $mongoDb)
    {
        $this->mongoDb = $mongoDb;
    }

    /**
     * @return \MongoDB\Database
     */
    protected function getMongo()
    {
        if (is_null($this->mongoDb)) {
            $this->mongoDb = MongoFactory::getDb();
        }

        return $this->mongoDb;
    }
}
