<?php
namespace Traits;

use Entities\Base;

trait DatabaseTrait
{
    public function preparePlaceHolders($arguments)
    {
        return str_pad('', count($arguments) * 2 - 1, '?,');
    }

    public function generateDuplicateKeySql(array $fields)
    {
        $statements = [];

        foreach ($fields as $field) {
            if ($field === 'id') {
                continue;
            }

            $statements[] = '`' . $field . '` = VALUES(`' . $field . '`)';
        }

        return implode(', ', $statements);
    }

    /**
     * Returns array of associative array keys, minus ID (this causes issues on duplicate updates; and is never
     * needed in an insert situation. (or update, besides on WHERE)
     *
     * @param $data
     * @return array
     */
    private function getFields($data):array
    {
        foreach ($this->skip as $skip) {
            if (array_key_exists($skip, $data)) {
                unset($data[$skip]);
            }
        }

        if (array_key_exists('id', $data)) {
            unset($data['id']);
        }

        return array_keys($data);
    }

    public function generateInsertSQL($data)
    {
        $fields = $this->getFields($data);

        $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`, `', $fields) . '`) VALUES';
        $sql .= ' (' .  $this->preparePlaceHolders($fields) . ')';

        return $sql;
    }

    public function generateUpdateSQL($data)
    {
        $fields = $this->getFields($data);
        $placeholders = implode(' = ?, ', $fields) . ' = ?';

        $sql = 'UPDATE `' . $this->table . '` SET ' . $placeholders . ' WHERE id = ?';

        return $sql;
    }

    public function generateBatchSQL($batch)
    {
        //@TODO does this need to be already an array? consistency check
        $fields = $this->getFields($batch[0]->toArray());

        $sql = 'INSERT INTO ' . $this->table . ' (`' . implode('`, `', $fields) . '`) VALUES';

        foreach ($batch as $index => $row) {
            $sql .= ' (' .  $this->preparePlaceHolders($fields) . '), ';
        }

        return rtrim($sql, ', ');
    }

    private function formatIfParamIsDateTime($param)
    {
        if ($param instanceof \DateTime) {
            $param = $param->format('Y-m-d H:i:s');
        }

        return $param;
    }

    private function prepareParametersForSQL(array $parameters)
    {
        foreach ($parameters as $column => $value) {
            if ($column === 'id') {
                unset($parameters[$column]);
                continue;
            }
            $param = $this->formatIfParamIsDateTime($value);
            $parameters[$column] = $param;
        }

        return $parameters;
    }

    public function generateInsertParameters($data)
    {
        if (array_key_exists('id', $data)) {
            unset($data['id']);
        }
        return array_values($data);
    }

    public function generateUpdateParameters($id, $data)
    {
        foreach ($this->skip as $skip) {
            if (array_key_exists($skip, $data)) {
                unset($data[$skip]);
            }
        }

        if (array_key_exists('id', $data)) {
            // Let's move this around
            unset($data['id']);
        }
        $data[] = $id;
        return array_values($data);
    }

    public function generateBatchParameters($batch)
    {
        $params = [];
        foreach ($batch as $index => $entity) {
            $data = $entity->toArray();
            $params[] = $this->prepareParametersForSQL($data);
        }
        return $this->flatten($params);
    }

    private function flatten(array $array)
    {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }
}
