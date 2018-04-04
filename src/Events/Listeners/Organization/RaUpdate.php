<?php

namespace Events\Listeners\Organization;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\RAP\Client;
use AllDigitalRewards\RAP\Exception\OrganizationException;
use Entities\Contact;
use Entities\Event;
use Entities\Organization;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Organization\UpdateOrganizationModel;

class RaUpdate extends AbstractListener
{
    /**
     * @var \AllDigitalRewards\RAP\Client
     */
    private $rapClient;

    /**
     * @var UpdateOrganizationModel
     */
    private $readOrganizationModel;

    public function __construct(
        MessagePublisher $publisher,
        Client $rapClient,
        UpdateOrganizationModel $readOrganizationModel
    ) {
        parent::__construct($publisher);
        $this->rapClient = $rapClient;
        $this->readOrganizationModel = $readOrganizationModel;
    }

    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->updateRaOrganization($event);
    }

    private function mapVendorOrganization(Organization $organization): \AllDigitalRewards\RAP\Entity\Organization
    {
        # Hydrate an Organization Object.
        $raOrganization = new \AllDigitalRewards\RAP\Entity\Organization;
        //@TODO This should be reviewed.. we don't need to set the unique id, cause it will never change on update.
        $raOrganization->setUniqueId($organization->getUniqueId());
        $raOrganization->setName($organization->getName());
        $raOrganization->setPhone($organization->getCompanyContact()->getPhone());
        $raOrganization->setAddress1($organization->getCompanyContact()->getAddress1());
        $raOrganization->setCity($organization->getCompanyContact()->getCity());
        $raOrganization->setState($organization->getCompanyContact()->getState());
        $raOrganization->setZip($organization->getCompanyContact()->getZip());

        if ($organization->hasAccountsPayableContact()) {
            $accountsPayableContactRef = $organization->getAccountsPayableContact();
            $accountsPayableContact = new \AllDigitalRewards\RAP\Entity\Contact();
            $this->mapVendorContact($accountsPayableContactRef, $accountsPayableContact);
            $raOrganization->setAccountsPayableContact($accountsPayableContact);
        }

        if ($organization->hasCompanyContact()) {
            $localCompanyContact = $organization->getCompanyContact();
            $raCompanyContact = new \AllDigitalRewards\RAP\Entity\Contact();
            $this->mapVendorContact($localCompanyContact, $raCompanyContact);
            $raOrganization->setCompanyContact($raCompanyContact);
        }

        return $raOrganization;
    }

    private function mapVendorContact(Contact $localContact, \AllDigitalRewards\RAP\Entity\Contact $vendorContact)
    {
        $vendorContact->setFirstname($localContact->getFirstname());
        $vendorContact->setLastname($localContact->getLastname());
        $vendorContact->setEmail($localContact->getEmail());
        $vendorContact->setPhone($localContact->getPhone());
        $vendorContact->setAddress1($localContact->getAddress1());
        $vendorContact->setAddress2($localContact->getAddress2());
        $vendorContact->setCity($localContact->getCity());
        $vendorContact->setState($localContact->getState());
        $vendorContact->setZip($localContact->getZip());
    }

    private function dispatchApiRequest(\AllDigitalRewards\RAP\Entity\Organization $organization)
    {
        try {
            # Update the Organization
            $this->rapClient->updateOrganization($organization);
            return true;
        } catch (OrganizationException $exception) {
            $this->setError($exception->getMessage());
            return false;
        }
    }

    private function updateRaOrganization(Event $event): bool
    {
        $organization = $this->readOrganizationModel->getSingle($event->getEntityId(), false);
        $raOrganization = $this->mapVendorOrganization($organization);

        if ($this->dispatchApiRequest($raOrganization) === true) {
            return true;
        }

        $event->setName('Organization.update.RaUpdate');
        $this->reQueueEvent($event);
        return false;
    }
}
