<?php
namespace Controllers;

use Controllers\Interfaces\OutputNormalizer;

abstract class AbstractOutputNormalizer implements OutputNormalizer
{
    private $output;

    public function __construct($output = null)
    {
        $this->set($output);
    }

    public function get()
    {
        return $this->output;
    }

    public function set($output)
    {
        if ($output !== null) {
            $this->output = $output;
        }
    }

    public function scrubList(array $collection, array $fields = []): array
    {
        $container = [];
        foreach ($collection as $entity) {
            $item = $entity->toArray();
            $container[] = $this->scrub($item, $fields);
        }

        return $container;
    }

    public function scrub(array $item, array $fields = []): array
    {
        array_push($fields, 'password');
        // some fields to unset;
        foreach ($item as $k => $v) {
            if (in_array($k, $fields)) {
                unset($item[$k]);
            }
        }
        return $item;
    }
}
