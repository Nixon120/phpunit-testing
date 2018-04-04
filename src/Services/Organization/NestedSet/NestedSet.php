<?php
namespace Services\Organization\NestedSet;

/**
 * Class NestedSet
 * @package Services\Organization\NestedSet
 */

/** Useful query for finding the top level parent, if you want to find the very next,
 * remove DESC and limit 1,1
 * @TODO: put these in methods for easy access
 *
SELECT parent.* FROM Organization node, Organization parent
WHERE ( node.lft BETWEEN parent.lft AND parent.rgt)
    AND node.id = 3
    AND node.parent_id IS NOT NULL
    ORDER BY parent.rgt - parent.lft DESC LIMIT 1
 *
 */

class NestedSet
{
    /**
     * @var \PDO
     */
    protected $db;

    /**
     * Name of the database table
     * @var string
     */
    public $table = '';

    public $descriptor = '';

    /**
     * NestedSet constructor.
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function setTable(string $table)
    {
        $this->table = $table;
    }

    public function getTable():string
    {
        return $this->table;
    }

    public function setDescriptor(string $descriptor)
    {
        $this->descriptor = $descriptor;
    }

    public function getDescriptor():string
    {
        return $this->descriptor;
    }

    private function getFurthestValue($direction)
    {
        $direction = $direction === 'right' ? 'rgt' : 'lft';
        $sql = "SELECT " . $direction . " FROM " . $this->table . " ORDER BY " . $direction . " DESC LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetchColumn();
    }

    /**
     * Creates the root node
     * @param string $id ID of root node of element
     * @return boolean
     */
    public function createRootNode($id)
    {
        $lft = $this->getFurthestValue('left');
        if (empty($lft)) {
            $lft = 1;
            $rgt = 2;
        } else {
            $lft = ((int) $lft) + 2;
            $rgt = ((int) $lft) + 1;
        }

        return $this->insertRootNode($id, $lft, $rgt);
    }

    private function insertRootNode($id, $lft, $rgt)
    {
        $sql = "UPDATE " . $this->table . " SET lft = ? , rgt = ?, lvl = ? WHERE id = ?";
        $this->db->beginTransaction();
        $sth = $this->db->prepare($sql);
        $arguments = [
            $lft,
            $rgt,
            1,
            $id
        ];
        $sth->execute($arguments);
        return $this->db->commit();
    }

    /**
     * Creates a new child node of the node with the given id
     * @param int $child id of the child node
     * @param int $parent id of the parent node
     * @return boolean true
     */
    public function insertChildNode($child, $parent)
    {
        $parent = $this->getNode($parent);
        return $this->insertNode($child, $parent);
    }

    private function modify(string $sql, array $arguments = []):bool
    {
        $sth = $this->db->prepare($sql);
        return $sth->execute($arguments);
    }

    /**
     * Creates a new node
     * @param int $child id of the child
     * @param \stdClass $parent parent
     * @return boolean true
     */
    private function insertNode($child, $parent)
    {
        $this->db->beginTransaction();
        $sql = "UPDATE " . $this->table . " SET rgt = rgt + 2 WHERE rgt >= ?";
        $this->modify($sql, [$parent->rgt]);

        $sql = "UPDATE " . $this->table . " SET lft = lft + 2 WHERE lft > ?";
        $this->modify($sql, [$parent->rgt]);

        $sql = "UPDATE " . $this->table . " SET lft = ?, rgt = ?, parent_id = ?, lvl = ? WHERE id = ?";
        $level = $parent->lvl+1;
        $this->modify($sql, [$parent->rgt, $parent->rgt+1, $parent->id, $level, $child]);

        return $this->db->commit();
    }

    /**
     * @param $id
     * @return mixed bool|object
     */
    private function getNode($id)
    {
        $sql = "SELECT id, lft, rgt, lvl FROM " . $this->table . " WHERE id = ?";
        $sth = $this->db->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_OBJ);
        $sth->execute([$id]);
        return $sth->fetch();
    }

    /**
     * Deletes a node an all it's children
     * @param integer $id id of the node to delete
     * @return boolean true
     */
    public function deleteNode($id)
    {
        $this->db->beginTransaction();
        $node = $this->getNode($id);

        $sql = "DELETE FROM " . $this->table . " WHERE lft BETWEEN ? AND ?";
        $arguments = [$node->lft, $node->rgt];
        $this->modify($sql, $arguments);

        $sql = "UPDATE " . $this->table . " SET lft = lft - ROUND((? - ? + 1)) WHERE lft > ?";
        $arguments = [$node->rgt, $node->lft, $node->rgt];
        $this->modify($sql, $arguments);

        $sql = "UPDATE " . $this->table . " SET rgt = rgt - ROUND((? - ? + 1)) WHERE rgt > ?";
        $arguments = [$node->rgt, $node->lft, $node->rgt];
        $this->modify($sql, $arguments);

        return $this->db->commit();
    }

    /**
     * Deletes a node and increases the level of all children by one
     * @param integer $id id of the node to delete
     * @return boolean true
     */
    public function deleteSingleNode($id)
    {
        $this->db->beginTransaction();
        $node = $this->getNode($id);

        $sql = "DELETE FROM " . $this->table . " WHERE lft = ?";
        $arguments = [$node->lft];
        $this->modify($sql, $arguments);

        $sql = "UPDATE " . $this->table . " SET lft = lft - 1, rgt = rgt - 1 WHERE lft BETWEEN ? AND ?";
        $arguments = [$node->lft, $node->rgt];
        $this->modify($sql, $arguments);

        $sql = "UPDATE " . $this->table . " SET lft = lft - 2 WHERE lft > ?";
        $arguments = [$node->rgt];
        $this->modify($sql, $arguments);

        $sql = "UPDATE " . $this->table . " SET rgt = rgt - 2 WHERE rgt > ?";
        $arguments = [$node->rgt];
        $this->modify($sql, $arguments);

        return $this->db->commit();
    }

    /**
     * Gets a multidimensional array containing the path to defined node
     * @param integer $id id of the node to which the path should point
     * @return array multidimensional array with the data of the nodes in the tree
     */
    public function getPath($id)
    {
        $sql = "SELECT p.id, p." . $this->descriptor
            . " FROM " . $this->table . " n, " . $this->table . " p "
            . " WHERE n.lft BETWEEN p.lft AND p.rgt AND n.id = ? ORDER BY p.lft;";

        $sth = $this->db->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_OBJ);
        $sth->execute([$id]);
        return $sth->fetch();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function error($id)
    {
        $errors = array ();
        $errors[] = 'There is no node with the given id!';
        $errors[] = 'No entries!';
        $errors[] = 'Node can\'t be moved to the right!';
        $errors[] = 'Node can\'t be moved to the left!';
        return $errors[$id];
    }
}
