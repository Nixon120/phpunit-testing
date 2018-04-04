<?php
namespace Repositories\Interfaces;

interface Repository
{
    public function getCollectionQuery():?string;
    public function getRepositoryEntity(); // add entityinterface
}
