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

    /**
     * @param $limit
     * @param $offset
     * @param $user_id
     * @return array
     */
    public function list($limit, $offset, int $user_id = null): array
    {
        $sql = "SELECT Sftp.* FROM `Sftp` ";
        if (!is_null($user_id)) {
            $sql .= "WHERE user_id = {$user_id} ";
        }
        $sql .= "ORDER BY `Sftp`.id DESC LIMIT {$limit} OFFSET {$offset}";

        $sth = $this->database->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(
            \PDO::FETCH_CLASS,
            $this->getRepositoryEntity()
        );
    }

    /**
     * @param $id
     * @return Sftp|null
     */
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

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function update($id, $data): bool
    {
        $sql = 'UPDATE Sftp SET host = ?, port = ?, file_path = ?, username = ?, password = ?';
        $args = [
            $data['host'],
            $data['port'],
            $data['file_path'],
            $data['username'],
            $data['password'],
        ];

        if (empty($data['key']) === false) {
            $sql .= ',  `key` = ? ';
            array_push($args, $data['key']);
        }

        $sql .= ' WHERE id = ? AND user_id = ?';
        array_push($args, $id);
        array_push($args, $data['user_id']);
        $sth = $this->database->prepare($sql);
        $this->database->beginTransaction();

        try {
            $sth->execute($args);
        } catch (\PDOException $e) {
            $this->errors[] = $e->getMessage();
        }
        $commit = $this->database->commit();
        return $commit;
    }
}
