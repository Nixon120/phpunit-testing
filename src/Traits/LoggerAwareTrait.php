<?php
namespace Traits;

use Factories\LoggerFactory;

trait LoggerAwareTrait
{
    protected function getLogger()
    {
        return LoggerFactory::getInstance();
    }
}
