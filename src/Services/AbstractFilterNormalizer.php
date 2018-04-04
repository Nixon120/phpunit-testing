<?php
namespace Services;

use Services\Interfaces\FilterNormalizer;

abstract class AbstractFilterNormalizer implements FilterNormalizer
{
    /**
     * @var ?array
     */
    private $input;

    public function __construct(?array $input = null)
    {
        $this->setInput($input);
    }

    public function getInput():?array
    {
        return $this->input;
    }

    public function setInput(?array $input = null)
    {
        if ($input !== null) {
            $this->input = $input;
        }
    }

    private function getFilterMethod($filter)
    {
        return "get" . str_replace(' ', '', ucwords(str_replace('_', ' ', $filter))) . 'Filter';
    }

    public function getFilterConditionSql():?string
    {
        $input = $this->getInput();
        if (!empty($input)) {
            $query = "";

            foreach ($input as $name => $value) {
                $filter = $this->getFilterMethod($name);
                if (method_exists($this, $filter) && $filterString = $this->$filter($value)) {
                    //@TODO and or separation here.. we can check to see if, getFilterCondition, which would return
                    //AND on default, or the value of the method exist
                    $query .= ' AND ' . $filterString;
                }
            }

            return $query;
        }

        return null;
    }

    public function getFilterConditionArgs():?array
    {
        $input = $this->getInput();
        $args = null;
        if (!empty($input)) {
            $args = [];
            foreach ($input as $name => $value) {
                $filter = $this->getFilterMethod($name);
                if (method_exists($this, $filter)) {
                    //@TODO and or separation here.. we can check to see if, getFilterCondition, which would return
                    //AND on default, or the value of the method exist
                    $argFilterMethod = $filter . 'Args';
                    $args = array_merge($args, $this->$argFilterMethod($value));
                }
            }
        }

        return $args;
    }
}
