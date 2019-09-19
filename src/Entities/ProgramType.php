<?php

namespace Entities;

class ProgramType extends Base
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    public $actions;

    private $availableActions = [
        'claim',
        'marketplace',
        'auto-redemption',
        'redemption-center',
        'sku-trigger',
        'sweepstakes',
        'file-upload',
        'rebates'
    ];

    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     * @throws \Exception
     */
    public function setActions(array $actions): void
    {
        foreach($actions as $action) {
            if($this->isActionValid($action) === false) {
                throw new \Exception('Action provided is invalid, it must be one of: ' . implode(', ', $this->availableActions));
            }
        }
        $this->actions = json_encode($actions, true);
    }

    private function isActionValid(string $action)
    {
        return in_array($action, $this->availableActions);
    }
}
