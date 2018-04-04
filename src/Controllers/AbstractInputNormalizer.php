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
}
