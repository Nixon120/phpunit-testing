<?php

namespace Repositories;

use Entities\ParticipantStatus;
use AllDigitalRewards\Services\Catalog\Client;
use AllDigitalRewards\StatusEnum\StatusEnum;
use Entities\Participant;
use PDO;
use Traits\LoggerAwareTrait;

class ParticipantStatusRepository extends BaseRepository
{
    use LoggerAwareTrait;

    protected $table = 'Participant';

    /**
     * @var Client
     */
    private $catalog;
    /**
     * @var StatusEnum
     */
    private $statusEnumService;

    public function __construct(PDO $database, Client $catalog)
    {
        parent::__construct($database);
        $this->catalog = $catalog;
    }

    /**
     * @return StatusEnum
     */
    public function getStatusEnumService(): StatusEnum
    {
        if ($this->statusEnumService === null) {
            $this->statusEnumService = new StatusEnum();
        }
        return $this->statusEnumService;
    }

    /**
     * @param StatusEnum $statusEnumService
     */
    public function setStatusEnumService(StatusEnum $statusEnumService): void
    {
        $this->statusEnumService = $statusEnumService;
    }

    public function getRepositoryEntity()
    {
        return ParticipantStatus::class;
    }

    /**
     * @param Participant $participant
     * @return int|mixed|string
     */
    public function hydrateParticipantStatusResponse(Participant $participant)
    {
        $status = $this->getStatusEnumService()::INACTIVE;
        //table should be updated with all statuses from ETL
        $participantStatus = $this->getCurrentParticipantStatus($participant);
        if ($participantStatus) {
            $status = $participantStatus->status;
        }

        return $this->getStatusEnumService()->hydrateStatus($status, true);
    }

    /**
     * @param Participant $participant
     * @param $status
     * @return bool
     */
    public function saveParticipantStatus(Participant $participant, $status)
    {
        $status = $this->getStatusEnumService()->hydrateStatus($status);
        $currentStatus = $this->getCurrentParticipantStatus($participant);
        //get current participant status if exists
        //if same as insert then just return
        if ($currentStatus && $currentStatus->status == $status) {
            return true;
        }
        $this->table = 'participant_status';
        $participantStatus = new ParticipantStatus();
        $participantStatus->setParticipantId($participant->getId());
        $participantStatus->setStatus((int)$status);
        $this->place($participantStatus);
        return true;
    }

    /**
     * @param $status
     * @return bool
     */
    public function hasValidStatus($status)
    {
        return $this->getStatusEnumService()->isValidStatus($status);
    }

    /**
     * For backwards compatibility
     *
     * @param $data
     * @return array
     */
    public function getHydratedStatusRequest($data): array
    {
        $data = $this->setStatusForBackwardsCompatibility($data);
        unset($data['inactive'], $data['frozen']);

        return $data;
    }

    /**
     * @param Participant $participant
     * @return mixed|null
     */
    private function getCurrentParticipantStatus(Participant $participant)
    {
        //get current status if exists
        $sql = "SELECT * FROM `participant_status` WHERE participant_id = ? ORDER BY id DESC LIMIT 1";
        $args = [$participant->getId()];
        return $this->query($sql, $args, ParticipantStatus::class);
    }

    /**
     * @param $data
     * @return array
     */
    private function setStatusForBackwardsCompatibility($data): array
    {
        //return early if status is passed in, this takes precedent
        //and tells us they have updated their sdk
        //validated status in middleware if present so we can be sure its valid
        if (array_key_exists('status', $data) === true) {
            $data['active'] = $this->getStatusEnumService()->isActive($data['status']) ? 1 : 0;
            return $data;
        }

        if (array_key_exists('active', $data) === true) {
            $data['status'] = (int)$data['active'] === 1
                ? $this->getStatusEnumService()::ACTIVE :
                $this->getStatusEnumService()::INACTIVE;
        }
        if (array_key_exists('frozen', $data) === true) {
            $data['status'] = (int)$data['frozen'] === 1
                ? $this->getStatusEnumService()::HOLD
                : $this->getStatusEnumService()::ACTIVE;
            $data['active'] = $data['status'] === 1 ? 1 : 0;
        }
        if (array_key_exists('inactive', $data) === true) {
            $data['status'] = (int)$data['inactive'] === 1
                ? $this->getStatusEnumService()::HOLD :
                $this->getStatusEnumService()::ACTIVE;
            $data['active'] = $data['status'] === 1 ? 1 : 0;
        }

        return $data;
    }
}
