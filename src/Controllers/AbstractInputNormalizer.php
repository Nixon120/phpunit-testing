<?php
namespace Controllers;

use Controllers\Interfaces\InputNormalizer;

abstract class AbstractInputNormalizer implements InputNormalizer
{
    private $input = [];

    private $offset;

    private $page;

    public function __construct(?array $input = null)
    {
        $this->setInput($input);
        $this->setPaging();
    }

    public function getInput():array
    {
        return $this->input;
    }

    public function setInput(?array $input)
    {
        if ($input !== null) {
            $this->input = $input;
        }
    }

    private function setPaging()
    {
        $this->page = !empty($this->input['page']) ? (int) $this->input['page'] : 1;
        $offset = !empty($this->input['offset']) ? (int) $this->input['offset'] : 30;
        $this->offset = ((int)($this->page-1) * (int)$offset);
        unset($this->input['offset'], $this->input['page']);
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Returns SQL for an array of potential sorts
     * 
     * @param array $sorts
     * A list-type array containing dictionary-type entries,
     *  each of which represents a single ORDER BY statement
     * 
     * @return string
     * - An empty string, if nothing specified
     * - An untrimmed (leading space) ORDER BY query statement
     * 
     * @example
     * $sorts: array(array('column': 'name'), array('column': 'favoriteColor', 'direction': 'desc'));
     * return: ' ORDER BY name ASC, favoriteColor DESC'
     */
    private function convertSortToOrderBy(array $sorts) : string
    {
        if (!$sorts || !count($sorts)) {
            return '';
        }
        $statements = array();
        foreach($sorts as $f) {
            $statement = $f['column'] . ' ';
            if (isset($sorts['direction'])) { // todo: figure out how query is escaped
                $statement .= $sorts['direction'];
            } else {
                $statement .= 'ASC';
            }
            $statements[] = $statement;
        }
        return (
            ' ORDER BY ' .
            implode(', ', $statements)
        );
    }

    public function getOrderBy(): string
    {
        if (!isset($this->input['sort'])) {
            return '';
        }
        return $this->convertSortToOrderBy(json_decode($this->input['sort'], true));
    }
}
