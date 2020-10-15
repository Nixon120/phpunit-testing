<?php

namespace Repositories;

use AllDigitalRewards\RewardStack\Entities\ParticipantStatus;
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
     * @return mixed|null
     */
    public function getCurrentParticipantStatus(Participant $participant)
    {
        //get current status if exists
        $sql = "SELECT * FROM `participant_status` WHERE participant_id = ? ORDER BY id DESC LIMIT 1";
        $args = [$participant->getId()];
        return $this->query($sql, $args, ParticipantStatus::class);
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
        list($status, $data) = $this->setStatusForBackwardsCompatibility($data);
        //if `status` exists this will take precedence
        //set active based on status passed in
        if (array_key_exists('status', $data) === true) {
            $status = $data['status'];
            $data['active'] = $this->getStatusEnumService()->isActive($status) ? 1 : 0;
        }

        unset($data['status'], $data['frozen']);

        return array($status, $data);
    }

    /**
     * @param $data
     * @return array
     */
    private function setStatusForBackwardsCompatibility($data): array
    {
        $status = $this->getStatusEnumService()::ACTIVE;
        if (array_key_exists('frozen', $data) === true) {
            $status = (int)$data['frozen'] === 1
                ? $this->getStatusEnumService()::HOLD
                : $this->getStatusEnumService()::ACTIVE;
        }
        if (array_key_exists('inactive', $data) === true) {
            $status = (int)$data['inactive'] === 1
                ? $this->getStatusEnumService()::HOLD :
                $this->getStatusEnumService()::ACTIVE;
        }
        if (array_key_exists('active', $data) === true) {
            $data['active'] = $this->getStatusEnumService()->isActive($status) ? 1 : 0;
        }

        return array($status, $data);
    }
}
