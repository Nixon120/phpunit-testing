<?php

namespace Events\Listeners\Program;

use AllDigitalRewards\RAP\Entity\Program;
use AllDigitalRewards\RAP\Exception\ProgramException;
use Entities\Event;
use League\Event\EventInterface;

/**
 * Listens for program update, updating program at RA
 * Class RaUpdate
 * @package Events\Listeners\Program
 */
class RaUpdate extends AbstractRaProgramListener
{
    /**
     * @param EventInterface $event
     * @return bool|void
     */
    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->updateRaProgram($event);
    }

    /**
     * @param Program $program
     * @return bool
     */
    private function dispatchApiRequest(Program $program): bool
    {
        try {
            # Create the Program
            $this->getRapClient()->updateProgram($program);
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
    private function updateRaProgram(Event $event): bool
    {
        $program = $this->getReadProgramModel()->getSingle($event->getEntityId(), false);
        $raOrganization = $this->mapVendorProgram($program);
        if ($raOrganization->isValid() && $this->dispatchApiRequest($raOrganization) === true) {
            return true;
        }

        $event->setName('Program.update.RaUpdate');
        $this->reQueueEvent($event);
        return false;
    }
}
