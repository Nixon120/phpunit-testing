<?php
namespace Repositories;

use Entities\Sftp;

class SftpRepository extends BaseRepository
{
    protected $table = 'Sftp';

    public function getRepositoryEntity()
    {
        return Sftp::class;
    }

    public function getCollectionQuery(): string
    {
        $this->orderBy = ' ORDER BY id DESC';
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getProgramIdContainer())) {
            $programIdString = implode(',', $this->getProgramIdContainer());
            $where = <<<SQL
WHERE Program.unique_id IN ({$programIdString})
SQL;
        }

        return <<<SQL
SELECT Sftp.*
FROM Sftp
LEFT JOIN Program ON Program.unique_id = Sftp.program
{$where}
SQL;
    }

    public function getSftpById($id): ?Sftp
    {
        $sql = <<<SQL
SELECT Sftp.* 
FROM `Sftp` 
WHERE `Sftp`.id = ?
SQL;

        $args = [$id];
        if (!$sftp = $this->query($sql, $args, Sftp::class)) {
            return null;
        }

        return $sftp;
    }
}
