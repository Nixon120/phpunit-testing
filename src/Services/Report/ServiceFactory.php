<?php

namespace Services\Report;

use Repositories\ContactRepository;
use Repositories\DomainRepository;
use Repositories\OrganizationRepository;
use Repositories\ProgramRepository;
use Services\AbstractServiceFactory;
use Services\Organization\Domain;
use Services\Organization\NestedSet\NestedSet;
use Services\Organization\UpdateOrganizationModel;
use Services\Program\Program;

class ServiceFactory extends AbstractServiceFactory
{
    public function getOrganizationService(): UpdateOrganizationModel
    {
        return new UpdateOrganizationModel(
            new OrganizationRepository($this->getDatabase()),
            $this->getDomainService()->repository,
            $this->getContactRepository(),
            $this->getNestedSet(),
            $this->getEventPublisher()
        );
    }

    public function getProgramService(): Program
    {
        return new Program($this->getProgramRepository(), $this->getContactRepository(), $this->getEventPublisher());
    }

    public function getEnrollmentReport()
    {
        return new Enrollment($this->getContainer()->get('report'));
    }

    public function getRedemptionReport()
    {
        return new Redemption($this->getContainer()->get('report'));

    }

    public function getTransactionReport()
    {
        return new Transaction($this->getContainer()->get('report'));
    }

    public function getPointBalanceReport()
    {
        return new PointBalance($this->getContainer()->get('report'));
    }

    public function getSweepstakeReport()
    {
        return new Sweepstake($this->getContainer()->get('report'));
    }

    private function getContactRepository()
    {
        return new ContactRepository($this->getDatabase());
    }

    public function getNestedSet(): NestedSet
    {
        $tree = new NestedSet($this->getDatabase());
        $tree->setTable('Organization');
        $tree->setDescriptor('name');
        return $tree;
    }

    public function getDomainService(): Domain
    {
        $repository = new DomainRepository($this->getDatabase());
        return new Domain($repository);
    }
}
