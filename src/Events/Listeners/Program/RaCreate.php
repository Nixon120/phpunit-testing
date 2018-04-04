<?php
namespace Events\Listeners\Program;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\RAP\Client;
use AllDigitalRewards\RAP\Exception\ProgramException;
use Entities\Contact;
use Entities\Event;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Program\Program;

class RaCreate extends AbstractListener
{
    /**
     * @var Client
     */
    private $rapClient;

    /**
     * @var Program
     */
    private $readProgramModel;

    public function __construct(
        MessagePublisher $publisher,
        Client $rapClient,
        Program $readProgramModel
    ) {
        parent::__construct($publisher);
        $this->rapClient = $rapClient;
        $this->readProgramModel = $readProgramModel;
    }

    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->createRaProgram($event);
    }

    private function mapVendorProgram(\Entities\Program $program): \AllDigitalRewards\RAP\Entity\Program
    {
        $raProgram = new \AllDigitalRewards\RAP\Entity\Program;
        $raProgram->setUniqueId($program->getUniqueId());
        $raProgram->setName($program->getName());
        $raProgram->setOrganizationId($program->getOrganization()->getUniqueId());
        $raProgram->setCostCenterId($program->getCostCenterId());

        if ($program->hasContact()) {
            $raContact = new \AllDigitalRewards\RAP\Entity\Contact();
            $localContact = $program->getContact();
            $this->mapVendorContact($localContact, $raContact);
            $raProgram->setContact($raContact);
        }

        return $raProgram;
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

    private function dispatchApiRequest(\AllDigitalRewards\RAP\Entity\Program $program): bool
    {
        try {
            # Create the Program
            $this->rapClient->createProgram($program);
            return true;
        } catch (ProgramException $exception) {
            $this->setError($exception->getMessage());
            return false;
        }
    }

    private function createRaProgram(Event $event): bool
    {
        $program = $this->readProgramModel->getSingle($event->getEntityId(), false);
        $raOrganization = $this->mapVendorProgram($program);
        if ($raOrganization->isValid() && $this->dispatchApiRequest($raOrganization) === true) {
            return true;
        }

        $event->setName('Program.create.RaCreate');
        $this->reQueueEvent($event);
        return false;
    }
}
