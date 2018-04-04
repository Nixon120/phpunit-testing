<?php

namespace Services\Organization;

use Repositories\ContactRepository;
use Repositories\DomainRepository;
use Repositories\WebhookRepository;
use Services\AbstractServiceFactory;
use Services\Organization\NestedSet\NestedSet;

class ServiceFactory extends AbstractServiceFactory
{
    private $webhookRepository;

    public function getService(): UpdateOrganizationModel
    {
        return new UpdateOrganizationModel(
            $this->getOrganizationRepository(),
            $this->getDomainService()->repository,
            $this->getContactRepository(),
            $this->getNestedSet(),
            $this->getEventPublisher()
        );
    }

    public function getInsertModel(): CreateOrganizationModel
    {
        return new CreateOrganizationModel(
            $this->getOrganizationRepository(),
            $this->getDomainService()->repository,
            $this->getContactRepository(),
            $this->getNestedSet(),
            $this->getEventPublisher()
        );
    }

    private function getContactRepository()
    {
        return new ContactRepository($this->getContainer()->get('database'));
    }

    public function getNestedSet(): NestedSet
    {
        $tree = new NestedSet($this->getContainer()->get('database'));
        $tree->setTable('Organization');
        $tree->setDescriptor('name');
        return $tree;
    }

    public function getDomainService(): Domain
    {
        $repository = new DomainRepository($this->getContainer()->get('database'));
        return new Domain($repository);
    }

    public function getWebhookRepository()
    {
        if ($this->webhookRepository === null) {
            $user = $this->getAuthenticatedUser();
            $this->webhookRepository = new WebhookRepository($this->getDatabase());
            $this->webhookRepository->setProgramIdContainer($user->getProgramOwnershipIdentificationCollection());
            $this->webhookRepository->setOrganizationIdContainer($user->getOrganizationOwnershipIdentificationCollection());
        }

        return $this->webhookRepository;
    }
}
