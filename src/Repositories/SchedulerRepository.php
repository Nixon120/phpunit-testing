<?php
namespace Repositories;

use Entities\AutoRedemption;

class SchedulerRepository extends BaseRepository
{
    protected $table = 'AutoRedemption';

    public function getRepositoryEntity()
    {
        return AutoRedemption::class;
    }

    public function getCollectionQuery(): string
    {
        return <<<SQL
SELECT AutoRedemption.* 
FROM AutoRedemption
WHERE 1=1 
SQL;
    }

    public function getAutoRedemption($id)
    {
        $sql = "SELECT * FROM AutoRedemption WHERE id = ?";

        $task = $this->query($sql, [$id], AutoRedemption::class);

        if (!$task) {
            return null;
        }

        return $task;
    }
}
