<?php
namespace Controllers;

use Controllers\Interfaces\InputNormalizer;

abstract class AbstractInputNormalizer implements InputNormalizer
{
    private $input = [];
    private $limit;
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

    public function getLimit():int
    {
        return $this->limit;
    }

    private function setPaging()
    {
        $this->page = !empty($this->input['page']) ? (int) $this->input['page'] : 1;
        $this->limit = 30;
        if(!empty($this->input['limit'])) {
          $this->limit = $this->input['limit'];
        }
        else if(!empty($this->input['offset'])) {
          $this->limit = $this->input['offset'];
        }
        unset($this->input['limit'], $this->input['offset'], $this->input['page']);
    }

    public function getPage(): int
    {
        return $this->page;
    }

}
