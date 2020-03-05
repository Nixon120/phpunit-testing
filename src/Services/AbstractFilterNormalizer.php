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

    public function returnArg($value)
    {
        return [$value];
    }

    public function setInput(?array $input = null)
    {
        if ($input !== null) {
            $this->input = $input;
        }
    }

    public function getOrderBy(): ?array
    {
        if(empty($this->input['orderBy']) || empty($this->input['orderBy']['field']) || empty($this->input['orderBy']['direction'])) {
            return null;
        }

        if(is_array($this->input['orderBy']['field']) === false) {
            // Only one sort field request
            return [
                $this->input['orderBy']['field'] => $this->input['orderBy']['direction']
            ];
        }

        $returnOrderByCollection = [];

        foreach($this->input['orderBy']['field'] as $key => $field) {
            $returnOrderByCollection[$field] = $this->input['orderBy']['direction'][$key];
        }

        return $returnOrderByCollection;
    }

    private function getFilterMethod($filter)
    {
        return "get" . str_replace(' ', '', ucwords(str_replace('_', ' ', $filter))) . 'Filter';
    }

    public function getFilterConditionSql():?string
    {
        $input = $this->getInput();
        unset($input['orderBy']);
        if (!empty($input)) {
            $query = "";

            foreach ($input as $name => $value) {
                $filter = $this->getFilterMethod($name);
                if (method_exists($this, $filter) && trim($value) !== '') {
                    $filterString = $this->$filter($value);
                    if ($filterString !== false) {
                        //@TODO and or separation here.. we can check to see if, getFilterCondition, which would return
                        //AND on default, or the value of the method exist
                        $query .= ' AND ' . $filterString;
                    }
                }
            }

            return $query;
        }

        return null;
    }

    public function getFilterConditionArgs():?array
    {
        $input = $this->getInput();
        unset($input['orderBy']);
        $args = null;
        if (!empty($input)) {
            $args = [];
            foreach ($input as $name => $value) {
                $filter = $this->getFilterMethod($name);
                if (method_exists($this, $filter) && trim($value) !== '') {
                    //@TODO and or separation here.. we can check to see if, getFilterCondition, which would return
                    //AND on default, or the value of the method exist
                    $argFilterMethod = $filter . 'Args';
                    if (is_array($value)) {
                        foreach ($value as $element) {
                            $args = array_merge($args, $this->$argFilterMethod($element));
                        }
                        continue;
                    }
                    $args = array_merge($args, $this->$argFilterMethod($value));
                }
            }
        }

        return $args;
    }
}
