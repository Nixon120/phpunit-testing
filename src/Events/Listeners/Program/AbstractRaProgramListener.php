<?php

namespace Events\Listeners\Program;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\RAP\Client;
use Entities\Contact;
use Events\Listeners\AbstractListener;
use Services\Program\Program;

abstract class AbstractRaProgramListener extends AbstractListener
{
    /**
     * @var Client
     */
    private $rapClient;

    /**
     * @var Program
     */
    private $readProgramModel;

    /**
     * AbstractRaProgramListener constructor.
     * @param MessagePublisher $publisher
     * @param Client $rapClient
     * @param Program $readProgramModel
     */
    public function __construct(
        MessagePublisher $publisher,
        Client $rapClient,
        Program $readProgramModel
    )
    {
        parent::__construct($publisher);
        $this->rapClient = $rapClient;
        $this->readProgramModel = $readProgramModel;
    }

    /**
     * @return Client
     */
    protected function getRapClient(): Client
    {
        return $this->rapClient;
    }

    /**
     * @param Client $rapClient
     */
    protected function setRapClient(Client $rapClient): void
    {
        $this->rapClient = $rapClient;
    }

    /**
     * @return Program
     */
    protected function getReadProgramModel(): Program
    {
        return $this->readProgramModel;
    }

    /**
     * @param Program $readProgramModel
     */
    protected function setReadProgramModel(Program $readProgramModel): void
    {
        $this->readProgramModel = $readProgramModel;
    }

    /**
     * @param \Entities\Program $program
     * @return string
     */
    protected function getProgramClassificationString(\Entities\Program $program)
    {
        $string = '';

        if (!empty($program->getProgramTypes())) {
            foreach ($program->getProgramTypes() as $type) {
                $string .= $type->getName() . ', ';
            }

            $string = rtrim($string, ', ');
        }

        return $string;
    }

    /**
     * @param \Entities\Program $program
     * @return \AllDigitalRewards\RAP\Entity\Program
     */
    protected function mapVendorProgram(\Entities\Program $program): \AllDigitalRewards\RAP\Entity\Program
    {
        $raProgram = new \AllDigitalRewards\RAP\Entity\Program;
        $raProgram->setUniqueId($program->getUniqueId());
        $raProgram->setName($program->getName());
        $raProgram->setOrganizationId($program->getOrganization()->getUniqueId());
        $raProgram->setCostCenterId($program->getCostCenterId());
        $raProgram->setClassification($this->getProgramClassificationString($program));

        if ($program->hasContact()) {
            $raContact = new \AllDigitalRewards\RAP\Entity\Contact();
            $localContact = $program->getContact();
            $this->mapVendorContact($localContact, $raContact);
            $raProgram->setContact($raContact);
        }

        return $raProgram;
    }

    /**
     * @param Contact $localContact
     * @param \AllDigitalRewards\RAP\Entity\Contact $vendorContact
     */
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
}
