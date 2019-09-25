<?php

namespace Events\Listeners\Program;

use AllDigitalRewards\RAP\Entity\Program;
use AllDigitalRewards\RAP\Exception\ProgramException;
use Entities\Event;
use League\Event\EventInterface;

/**
 * Listens for program creation, creating the program at RA
 *
 * Class RaCreate
 * @package Events\Listeners\Program
 */
class RaCreate extends AbstractRaProgramListener
{
    /**
     * @param EventInterface $event
     * @return bool|void
     */
    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->createRaProgram($event);
    }

    /**
     * @param Program $program
     * @return bool
     */
    private function dispatchApiRequest(Program $program): bool
    {
        try {
            # Create the Program
            $this->getRapClient()->createProgram($program);
            return true;
        } catch (ProgramException $exception) {
            $this->setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @param Event $event
     * @return bool
     */
    private function createRaProgram(Event $event): bool
    {
        $program = $this->getReadProgramModel()->getSingle($event->getEntityId(), false);
        $raOrganization = $this->mapVendorProgram($program);
        if ($raOrganization->isValid() && $this->dispatchApiRequest($raOrganization) === true) {
            return true;
        }

        $event->setName('Program.create.RaCreate');
        $this->reQueueEvent($event);
        return false;
    }
}
