<?php
namespace Controllers\Interfaces;

interface OutputNormalizer
{

    public function getList():array;

    public function get();

    public function set($output);
}
