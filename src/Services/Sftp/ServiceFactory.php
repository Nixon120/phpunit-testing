<?php

namespace Services\Sftp;

use Repositories\SftpRepository;
use Services\AbstractServiceFactory;

class ServiceFactory extends AbstractServiceFactory
{
    public function getSftpRepository()
    {
        return new SftpRepository($this->getDatabase());
    }
}
